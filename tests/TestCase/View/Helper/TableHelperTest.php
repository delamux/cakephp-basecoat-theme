<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\TableHelper;
use Cake\Datasource\Paging\PaginatedResultSet;
use ArrayIterator;

class TableHelperTest extends HelperTestCase
{
    public function testRenderKeyedRows(): void
    {
        $table = new TableHelper($this->View);
        $this->assertSame(
            '<div class="table-container"><table class="table"><caption>Invoices</caption>'
            . '<thead><tr><th>Invoice</th><th class="text-right">Amount</th></tr></thead>'
            . '<tbody><tr data-id="1"><td>INV&lt;1&gt;</td><td class="text-right">$250.00</td></tr></tbody>'
            . '<tfoot><tr><td>Total</td><td>$250.00</td></tr></tfoot></table></div>',
            $table->render(
                ['invoice' => 'Invoice', 'amount' => ['label' => 'Amount', 'class' => 'text-right']],
                [['_row' => ['data-id' => 1], 'amount' => '$250.00', 'invoice' => 'INV<1>']],
                ['caption' => 'Invoices', 'footer' => ['Total', '$250.00']],
            ),
        );
    }

    public function testListRowsCellOptionsAndNoContainer(): void
    {
        $table = new TableHelper($this->View, ['container' => false, 'class' => 'text-sm']);
        $this->assertSame(
            '<table class="table text-sm"><thead><tr><th>A</th><th>B</th></tr></thead>'
            . '<tbody><tr><td><b>x</b></td><td colspan="2" class="font-medium">y</td></tr></tbody></table>',
            $table->render(['A', 'B'], [[
                ['content' => '<b>x</b>', 'escape' => false],
                ['content' => 'y', 'colspan' => 2, 'class' => 'font-medium'],
            ]]),
        );
    }

    public function testEmptyMessage(): void
    {
        $table = new TableHelper($this->View, ['empty' => 'No results.']);
        $this->assertStringContainsString(
            '<tbody><tr><td colspan="2" class="text-center text-muted-foreground">No results.</td></tr></tbody>',
            $table->render(['a' => 'A', 'b' => 'B'], []),
        );
    }

    /**
     * Registers a paginated result so PaginatorHelper knows the current sort.
     */
    private function setPaging(?string $sort = null, ?string $direction = null): void
    {
        $this->View->set('articles', new PaginatedResultSet(new ArrayIterator([]), [
            'count' => 0, 'currentPage' => 1, 'perPage' => 20, 'totalCount' => 0, 'pageCount' => 1,
            'hasPrevPage' => false, 'hasNextPage' => false, 'sort' => $sort, 'direction' => $direction,
            'sortDefault' => false, 'directionDefault' => false, 'completeSort' => [], 'alias' => 'Articles',
            'scope' => null, 'limit' => null, 'start' => 0, 'end' => 0, 'requestedPage' => 1,
        ]));
    }

    public function testSortableHeaderUsesPaginatorWithoutIcons(): void
    {
        $this->setPaging();
        $table = new TableHelper($this->View, ['sortIcons' => false]);
        $out = $table->render(['title' => ['label' => 'Title', 'sort' => true]], []);
        $this->assertStringContainsString('<th><a href="/articles?sort=title&amp;direction=asc">Title</a></th>', $out);
    }

    public function testUnsortedSortableColumnShowsNeutralIndicator(): void
    {
        $this->setPaging();
        $out = (new TableHelper($this->View))->render(['title' => ['label' => 'Title<b>', 'sort' => true]], []);

        $this->assertStringContainsString('href="/articles?sort=title&amp;direction=asc"', $out);
        $this->assertStringContainsString('Title&lt;b&gt;', $out, 'The label is still escaped');
        $this->assertStringContainsString('data-sort-icon="none"', $out);
        $this->assertStringNotContainsString('aria-sort', $out);
    }

    public function testActiveAscendingColumnShowsArrowAndAriaSort(): void
    {
        $this->setPaging('title', 'asc');
        $out = (new TableHelper($this->View))->render([
            'title' => ['label' => 'Title', 'sort' => true],
            'created' => ['label' => 'Created', 'sort' => true],
        ], []);

        $this->assertMatchesRegularExpression('/<th aria-sort="ascending"><a [^>]*sort=title&amp;direction=desc/', $out);
        $this->assertStringContainsString('data-sort-icon="asc"', $out);
        $this->assertMatchesRegularExpression('/<th><a [^>]*sort=created[^>]*>.*data-sort-icon="none"/', $out);
    }

    public function testActiveDescendingColumnUsesFieldNameFromSortOption(): void
    {
        $this->setPaging('Articles.published', 'desc');
        $out = (new TableHelper($this->View))->render(['date' => ['label' => 'Date', 'sort' => 'Articles.published']], []);

        $this->assertStringContainsString('aria-sort="descending"', $out);
        $this->assertStringContainsString('data-sort-icon="desc"', $out);
    }
}
