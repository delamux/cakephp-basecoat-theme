<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\Listing\ListFilter;
use BasecoatTheme\View\Helper\TableToolbarHelper;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\View\View;

class TableToolbarHelperTest extends HelperTestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function controls(): array
    {
        return [
            'q' => ['type' => 'search', 'label' => 'Search'],
            'status' => ['type' => 'select', 'label' => 'Status', 'options' => ['paid' => 'Paid', 'failed' => 'Failed']],
            'created' => ['type' => 'dateRange', 'label' => 'Created'],
        ];
    }

    public function testControlsArray(): void
    {
        $out = (new TableToolbarHelper($this->View))->render(['filter' => $this->controls()]);

        $this->assertStringContainsString('role="search"', $out);
        $this->assertStringContainsString('method="get"', $out);
        $this->assertStringContainsString('type="search"', $out);
        $this->assertStringContainsString('<select', $out);
        $this->assertStringContainsString('name="created[from]"', $out);
        $this->assertStringContainsString('name="created[to]"', $out);
        $this->assertStringContainsString('class="popover"', $out);
        $this->assertStringContainsString('>Filter</button>', $out);
    }

    public function testListFilterInstance(): void
    {
        $connection = new Connection(['driver' => new Sqlite(['database' => ':memory:'])]);
        $connection->execute('CREATE TABLE articles (id INTEGER PRIMARY KEY, title VARCHAR(100), created DATETIME)');
        $table = new Table(['alias' => 'Articles', 'table' => 'articles', 'connection' => $connection]);
        $table->addBehavior('Search.Search');
        $table->getBehavior('Search')->searchManager()
            ->add('q', 'BasecoatTheme.Contains', ['fields' => ['title']])
            ->add('created', 'BasecoatTheme.DateRange', ['label' => 'Created']);
        $list = new ListFilter(new ServerRequest(['url' => '/']), $table);

        $out = (new TableToolbarHelper($this->View))->render(['filter' => $list]);

        $this->assertStringContainsString('type="search"', $out);
        $this->assertStringContainsString('name="created[from]"', $out);
        $this->assertStringContainsString('name="created[to]"', $out);
    }

    public function testKeepsSort(): void
    {
        $view = new View(new ServerRequest(['url' => '/x', 'query' => ['sort' => 'name', 'direction' => 'asc']]));
        $out = (new TableToolbarHelper($view))->render(['filter' => $this->controls()]);

        $this->assertStringContainsString('<input type="hidden" name="sort" value="name">', $out);
        $this->assertStringContainsString('<input type="hidden" name="direction" value="asc">', $out);
    }

    public function testHiddenFieldsKeepContext(): void
    {
        $out = (new TableToolbarHelper($this->View))->render([
            'filter' => $this->controls(),
            'hidden' => ['path' => 'A & B/C', 'view' => 'grid', 'empty' => '', 'none' => null],
            'clear' => '/files',
        ]);

        $this->assertStringContainsString('<input type="hidden" name="path" value="A &amp; B/C">', $out);
        $this->assertStringContainsString('<input type="hidden" name="view" value="grid">', $out);
        $this->assertStringNotContainsString('name="empty"', $out);
        $this->assertStringNotContainsString('name="none"', $out);
        preg_match('/<form[^>]*>/', $out, $form);
        $this->assertStringNotContainsString(' hidden=', $form[0], 'Not rendered as a form attribute');
        // "Clear" drops the filters but keeps the context.
        $this->assertStringContainsString('href="/files?path=A+%26+B%2FC&amp;view=grid"', $out);
    }

    public function testHtmxAttributes(): void
    {
        $out = (new TableToolbarHelper($this->View))->render([
            'filter' => $this->controls(),
            'htmx' => ['target' => 'orders'],
        ]);

        $this->assertStringContainsString('hx-get="/articles/index"', $out);
        $this->assertStringContainsString('hx-target="#orders"', $out);
        $this->assertStringContainsString('hx-select="#orders"', $out);
        $this->assertStringContainsString('hx-swap="outerHTML"', $out);
        $this->assertStringContainsString('hx-push-url="true"', $out);
        $this->assertStringContainsString('hx-trigger="input changed delay:400ms from:find input[type=search], change, submit"', $out);

        $plain = (new TableToolbarHelper($this->View))->render(['filter' => $this->controls()]);
        $this->assertStringNotContainsString('hx-', $plain);
    }

    public function testActionsAndUrl(): void
    {
        $out = (new TableToolbarHelper($this->View))->render([
            'filter' => $this->controls(),
            'actions' => '<a class="btn" href="/new">New</a>',
            'url' => '/orders',
            'submit' => 'Go',
        ]);

        $this->assertStringContainsString('<a class="btn" href="/new">New</a>', $out);
        $this->assertStringContainsString('action="/orders"', $out);
        $this->assertStringContainsString('>Go</button>', $out);
    }

    public function testEmpty(): void
    {
        $this->assertSame('', (new TableToolbarHelper($this->View))->render());
        $this->assertSame('', (new TableToolbarHelper($this->View))->render(['filter' => []]));
    }

    /**
     * @param array<string, mixed> $query Query string.
     */
    private function listFilter(array $query, bool $search): ListFilter
    {
        $connection = new Connection(['driver' => new Sqlite(['database' => ':memory:'])]);
        $connection->execute('CREATE TABLE articles (id INTEGER PRIMARY KEY, title VARCHAR(100), created DATETIME)');
        $table = new Table(['alias' => 'Articles', 'table' => 'articles', 'connection' => $connection]);
        $table->addBehavior('Search.Search');
        $table->getBehavior('Search')->searchManager()
            ->add('q', 'BasecoatTheme.Contains', ['fields' => ['title']]);
        if ($search) {
            $table->find('search', search: $query)->all();
        }

        return new ListFilter(new ServerRequest(['url' => '/x', 'query' => $query]), $table);
    }

    public function testClearLinkForFilteredListFilter(): void
    {
        $query = ['q' => 'abc', 'sort' => 'name', 'direction' => 'asc', 'page' => '3'];
        $view = new View(new ServerRequest(['url' => '/x', 'query' => $query]));
        $out = (new TableToolbarHelper($view))->render(['filter' => $this->listFilter($query, true)]);

        $this->assertStringContainsString('class="btn" data-variant="ghost">Clear</a>', $out);
        $this->assertStringContainsString('href="/x?sort=name&amp;direction=asc"', $out);
        $this->assertStringNotContainsString('q=abc"', $out);
    }

    public function testNoClearLinkWhenNotFiltered(): void
    {
        $out = (new TableToolbarHelper($this->View))->render(['filter' => $this->listFilter([], true)]);
        $this->assertStringNotContainsString('Clear', $out);
    }

    public function testClearOptionIsNotRenderedAsFormAttribute(): void
    {
        $helper = new TableToolbarHelper($this->View);
        foreach ([true, '/orders', ['action' => 'index']] as $clear) {
            $out = $helper->render(['filter' => $this->controls(), 'clear' => $clear]);
            preg_match('/<form[^>]*>/', $out, $m);
            $this->assertNotEmpty($m);
            $this->assertStringNotContainsString('clear=', $m[0]);
        }
    }

    public function testClearOptionForArrayControls(): void
    {
        $helper = new TableToolbarHelper($this->View);
        $this->assertStringNotContainsString('Clear', $helper->render(['filter' => $this->controls()]));
        $this->assertStringContainsString('>Clear</a>', $helper->render(['filter' => $this->controls(), 'clear' => true]));
        $this->assertStringContainsString(
            'href="/orders"',
            $helper->render(['filter' => $this->controls(), 'clear' => '/orders']),
        );
    }

    public function testClearUrlsAreEscapedOnce(): void
    {
        $query = ['q' => 'abc', 'sort' => 'name', 'direction' => 'asc'];
        $helper = new TableToolbarHelper(new View(new ServerRequest(['url' => '/x', 'query' => $query])));

        $out = $helper->render(['filter' => $this->controls(), 'clear' => ['controller' => 'Orders', 'action' => 'index', '?' => ['view' => 'all']]]);
        $this->assertStringContainsString('href="/orders?sort=name&amp;direction=asc&amp;view=all"', $out);
        $this->assertStringNotContainsString('&amp;amp;', $out);

        $out = $helper->render(['filter' => $this->controls(), 'clear' => '/orders?view=all&tab=open']);
        $this->assertStringContainsString('href="/orders?sort=name&amp;direction=asc&amp;view=all&amp;tab=open"', $out);
        $this->assertStringNotContainsString('&amp;amp;', $out);
        $this->assertSame(1, substr_count((string)preg_replace('/.*href="([^"]*)"[^>]*>Clear.*/s', '$1', $out), '?'));
    }

    public function testClearUrlWithoutKeptParamsKeepsItsQuery(): void
    {
        $helper = new TableToolbarHelper($this->View);
        $out = $helper->render(['filter' => $this->controls(), 'clear' => '/orders?view=all&tab=open']);
        $this->assertStringContainsString('href="/orders?view=all&amp;tab=open"', $out);
    }

    public function testEscapes(): void
    {
        $out = (new TableToolbarHelper($this->View))->render([
            'filter' => [
                'q' => ['type' => 'search', 'label' => '<b>Find</b>'],
                'status' => ['type' => 'select', 'label' => '<i>Status</i>', 'options' => ['x' => '<u>Open</u>']],
                'owner' => ['type' => 'text', 'label' => '<s>Owner</s>'],
            ],
            'submit' => '<em>Go</em>',
            'clear' => '/orders?a="b"',
            'escape' => false,
        ]);
        foreach (['<b>Find', '<i>Status', '<u>Open', '<s>Owner', '<em>Go', '"b"'] as $raw) {
            $this->assertStringNotContainsString($raw, $out);
        }
        $this->assertStringContainsString('&lt;b&gt;Find&lt;/b&gt;', $out);
        $this->assertStringContainsString('&lt;u&gt;Open&lt;/u&gt;', $out);
        $this->assertStringContainsString('&lt;em&gt;Go&lt;/em&gt;', $out);
        $this->assertStringNotContainsString('escape=', $out);
    }

    public function testControlAttributesGoOnTheInput(): void
    {
        $out = (new TableToolbarHelper($this->View))->render(['filter' => [
            'q' => ['type' => 'search', 'label' => 'Search', 'attributes' => ['data-filter-input' => '#rows']],
            'status' => ['type' => 'select', 'label' => 'Status', 'options' => ['a' => 'A'], 'attributes' => ['data-filter-facet' => '#rows', 'data-filter-key' => 'status']],
            'name' => ['type' => 'text', 'label' => 'Name', 'attributes' => ['data-x' => '"quoted"', 'class' => 'extra']],
        ]]);

        $this->assertMatchesRegularExpression('/<input[^>]*type="search"[^>]*data-filter-input="#rows"|<input[^>]*data-filter-input="#rows"[^>]*type="search"/', $out);
        $this->assertMatchesRegularExpression('/<select[^>]*data-filter-facet="#rows"[^>]*>/', $out);
        $this->assertStringContainsString('data-filter-key="status"', $out);
        $this->assertStringContainsString('data-x="&quot;quoted&quot;"', $out);
        $this->assertMatchesRegularExpression('/<input[^>]*name="name"[^>]*class="[^"]*extra/', $out);
        $this->assertStringNotContainsString('attributes=', $out);
    }

    public function testControlAttributesCannotReplaceTheName(): void
    {
        $out = (new TableToolbarHelper($this->View))->render(['filter' => [
            'q' => ['type' => 'search', 'label' => 'Search', 'attributes' => ['name' => 'other', 'type' => 'text']],
        ]]);

        $this->assertStringContainsString('name="q"', $out);
        $this->assertStringContainsString('type="search"', $out);
        $this->assertStringNotContainsString('name="other"', $out);
    }
}
