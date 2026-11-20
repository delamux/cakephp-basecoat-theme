<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Listing;

use BasecoatTheme\Listing\ListFilter;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * List search and filters (friendsofcake/search with the theme filters `Contains`, `Equals` and
 * `DateRange`), the controls of the filter bar and the CSV export, on an in-memory SQLite database.
 */
class ListFilterTest extends TestCase
{
    private Table $articles;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = new Connection(['driver' => new Sqlite(['database' => ':memory:'])]);
        $connection->execute('CREATE TABLE categories (id INTEGER PRIMARY KEY, name VARCHAR(50) NOT NULL)');
        $connection->execute('CREATE TABLE articles (
            id INTEGER PRIMARY KEY, title VARCHAR(100) NOT NULL, body TEXT, category_id INTEGER,
            published BOOLEAN NOT NULL DEFAULT 0, created DATETIME
        )');
        $connection->execute("INSERT INTO categories (id, name) VALUES (1, 'News'), (2, 'Guides')");
        $rows = [
            [1, 'Hello World', 'First post', 1, true, '2026-01-10 09:00:00'],
            [2, 'Second news', 'About the world cup', 1, false, '2026-01-20 23:59:00'],
            [3, 'Install guide', '100% complete', 2, true, '2026-02-01 00:00:00'],
            [4, '=HYPERLINK("http://evil")', null, null, false, null],
        ];
        foreach ($rows as $row) {
            $connection->insert(
                'articles',
                array_combine(['id', 'title', 'body', 'category_id', 'published', 'created'], $row),
                ['published' => 'boolean'],
            );
        }

        $this->articles = new Table(['alias' => 'Articles', 'table' => 'articles', 'connection' => $connection]);
        $categories = new Table(['alias' => 'Categories', 'table' => 'categories', 'connection' => $connection]);
        $categories->setDisplayField('name');
        $this->articles->belongsTo('Categories', ['targetTable' => $categories]);
        $this->articles->addBehavior('Search.Search');
        $this->articles->getBehavior('Search')->searchManager()
            ->add('q', 'BasecoatTheme.Contains', ['fields' => ['title', 'body']])
            ->add('category_id', 'BasecoatTheme.Equals', ['label' => 'Category'])
            ->add('published', 'Search.Boolean', ['label' => 'Published'])
            ->add('created', 'BasecoatTheme.DateRange', ['label' => 'Created']);
    }

    /**
     * @param array<string, mixed> $query Query string.
     * @return list<int>
     */
    private function ids(array $query): array
    {
        $result = $this->articles->find('search', search: $query)->orderByAsc('Articles.id')->all();

        return array_map(fn($article) => (int)$article->id, $result->toList());
    }

    /**
     * @param array<string, mixed> $query Query string.
     * @param array<string, mixed> $config Config.
     */
    private function list(array $query = [], array $config = []): ListFilter
    {
        return new ListFilter(new ServerRequest(['query' => $query]), $this->articles, $config + [
            'export' => [
                'id' => 'Id',
                'title' => 'Title',
                'category.name' => 'Category',
                'published' => 'Published',
                'created' => 'Created',
            ],
        ]);
    }

    public function testWithoutParametersEverythingIsListed(): void
    {
        $this->assertSame([1, 2, 3, 4], $this->ids([]));
    }

    public function testSearchMatchesAnyFieldIgnoringCaseAndEveryWordMustMatch(): void
    {
        $this->assertSame([1, 2], $this->ids(['q' => 'WORLD']), 'title or body, any case');
        $this->assertSame([2], $this->ids(['q' => 'world  news']), 'every word must match some field');
        $this->assertSame([], $this->ids(['q' => 'nothing-like-this']));
        $this->assertSame([1, 2, 3, 4], $this->ids(['q' => '   ']), 'blank search is ignored');
        $this->assertSame([1, 2, 3, 4], $this->ids(['q' => ['world']]), 'arrays are ignored');
    }

    public function testWildcardsInTheSearchAreLiteral(): void
    {
        $this->assertSame([3], $this->ids(['q' => '100%']));
        $this->assertSame([3], $this->ids(['q' => '%']), '`%` is a character, not "anything"');
        $this->assertSame([], $this->ids(['q' => 'Hello_World']), '`_` is a character, not "any character"');
    }

    public function testBooleanFilter(): void
    {
        $this->assertSame([1, 3], $this->ids(['published' => '1']));
        $this->assertSame([2, 4], $this->ids(['published' => '0']));
        $this->assertSame([1, 2, 3, 4], $this->ids(['published' => 'maybe']), 'other values are ignored');
    }

    public function testEqualsOnlyAcceptsValuesOfTheColumnType(): void
    {
        $this->assertSame([1, 2], $this->ids(['category_id' => '1']));
        $this->assertSame([1, 2, 3, 4], $this->ids(['category_id' => 'abc']), 'not an integer: ignored, no SQL error');
        $this->assertSame([1, 2, 3, 4], $this->ids(['category_id' => ['1']]), 'arrays are ignored');
    }

    public function testDateRangeIncludesBothDays(): void
    {
        $this->assertSame([1, 2], $this->ids(['created' => ['from' => '2026-01-10', 'to' => '2026-01-20']]));
        $this->assertSame([3], $this->ids(['created' => ['from' => '2026-02-01']]));
        $this->assertSame([1], $this->ids(['created' => ['to' => '2026-01-10']]));
        $this->assertSame(
            [1, 2, 3, 4],
            $this->ids(['created' => ['from' => '2026-13-45', 'to' => 'yesterday']]),
            'invalid dates are ignored',
        );
        $this->assertSame([1, 2, 3, 4], $this->ids(['created' => '2026-01-10']), 'a plain value is ignored');
    }

    public function testFiltersCombine(): void
    {
        $this->assertSame([1], $this->ids(['q' => 'world', 'published' => '1', 'category_id' => '1']));
    }

    public function testTheBarControlsComeFromTheSearchFilters(): void
    {
        $controls = $this->list()->controls();

        $this->assertSame(['q', 'category_id', 'published', 'created'], array_keys($controls));
        $this->assertSame('search', $controls['q']['type']);
        $this->assertSame(
            ['type' => 'select', 'label' => 'Category', 'options' => [2 => 'Guides', 1 => 'News']],
            $controls['category_id'],
            'related records by display field',
        );
        $this->assertSame('select', $controls['published']['type']);
        $this->assertSame(['1', '0'], array_map('strval', array_keys($controls['published']['options'])));
        $this->assertSame('dateRange', $controls['created']['type']);
        $this->assertSame('Created', $controls['created']['label']);
    }

    public function testTheListIsFilteredOnlyWhenAFilterApplies(): void
    {
        $this->articles->find('search', search: ['published' => 'maybe'])->all();
        $this->assertFalse($this->list()->isFiltered(), 'an invalid value filters nothing');

        $this->articles->find('search', search: ['published' => '1'])->all();
        $this->assertTrue($this->list()->isFiltered());
    }

    public function testExportIsRequestedWithTheQueryStringAndKeepsTheFilters(): void
    {
        $list = $this->list(['q' => 'world', 'page' => '3', 'sort' => 'title']);
        $this->assertFalse($list->isExport());
        $this->assertSame(['q' => 'world', 'sort' => 'title', 'export' => 'csv'], $list->exportQuery());
        $this->assertTrue($this->list(['export' => 'csv'])->isExport());
    }

    /**
     * @param array<string, mixed> $query Query string.
     * @param array<string, mixed> $config Config.
     */
    private function csv(array $query, array $config = []): string
    {
        $handle = fopen('php://memory', 'w+');
        $this->assertNotFalse($handle);
        $this->list($query, $config)->writeCsv(
            $this->articles->find('search', search: $query)->contain(['Categories']),
            $handle,
        );
        rewind($handle);

        return (string)stream_get_contents($handle);
    }

    /**
     * @return list<int> First column of each data row.
     */
    private function csvIds(string $csv): array
    {
        $lines = array_slice(explode("\n", trim(substr($csv, 3))), 1);

        return array_map(fn($line) => (int)strtok($line, ','), $lines);
    }

    public function testCsvHasTheFilteredRowsWithReadableValues(): void
    {
        $csv = $this->csv(['published' => '1']);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'BOM: Excel opens UTF-8 correctly');
        $this->assertSame([
            'Id,Title,Category,Published,Created',
            '1,"Hello World",News,Yes,"2026-01-10 09:00:00"',
            '3,"Install guide",Guides,Yes,"2026-02-01 00:00:00"',
        ], explode("\n", trim(substr($csv, 3))));
    }

    public function testCsvNeutralisesSpreadsheetFormulas(): void
    {
        $csv = $this->csv(['q' => 'hyperlink']);

        $this->assertStringContainsString("4,\"'=HYPERLINK(\"\"http://evil\"\")\",,No,", $csv);
    }

    public function testCsvFollowsTheListSortAndReadsInChunks(): void
    {
        $csv = $this->csv(['sort' => 'title', 'direction' => 'desc'], ['chunkSize' => 1]);
        $this->assertSame([2, 3, 1, 4], $this->csvIds($csv), 'title desc, every row once although each read brings one row');

        $csv = $this->csv(['sort' => 'nope; DROP', 'direction' => 'sideways'], ['chunkSize' => 3]);
        $this->assertSame([1, 2, 3, 4], $this->csvIds($csv), 'unknown sort: primary key order');
    }

    public function testCsvResponseIsADownload(): void
    {
        $response = $this->list(['export' => 'csv'])->csv($this->articles->find(), new Response(), 'articles');

        $this->assertSame('text/csv', $response->getType());
        $this->assertMatchesRegularExpression(
            '/attachment; filename="articles-\d{4}-\d{2}-\d{2}\.csv"/',
            $response->getHeaderLine('Content-Disposition'),
        );
        ob_start();
        echo $response->getBody();
        $body = (string)ob_get_clean();
        $this->assertStringContainsString('"Hello World"', $body);
    }
}
