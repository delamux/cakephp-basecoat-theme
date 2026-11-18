<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use ArrayIterator;
use BasecoatTheme\View\Helper\PaginationHelper;
use Cake\Datasource\Paging\PaginatedResultSet;

class PaginationHelperTest extends HelperTestCase
{
    protected function paginate(int $page, int $pageCount): void
    {
        $this->View->set('articles', new PaginatedResultSet(new ArrayIterator([]), [
            'count' => 10, 'currentPage' => $page, 'perPage' => 10, 'totalCount' => $pageCount * 10,
            'pageCount' => $pageCount, 'hasPrevPage' => $page > 1, 'hasNextPage' => $page < $pageCount,
            'sort' => null, 'direction' => null, 'sortDefault' => false, 'directionDefault' => false,
            'completeSort' => [], 'alias' => 'Articles', 'scope' => null, 'limit' => null,
            'start' => ($page - 1) * 10 + 1, 'end' => $page * 10, 'requestedPage' => $page,
        ]));
    }

    public function testNoPagingRendersNothing(): void
    {
        $this->assertSame('', (new PaginationHelper($this->View))->render());
    }

    public function testRender(): void
    {
        $this->paginate(5, 10);
        $pagination = new PaginationHelper($this->View);
        $out = $pagination->render();

        $this->assertStringStartsWith('<nav aria-label="pagination" role="navigation" class="mx-auto flex w-full justify-center"><ul class="flex flex-row items-center gap-1">', $out);
        $this->assertMatchesRegularExpression('#<li><a class="btn" data-variant="ghost" rel="prev" href="/articles\?page=4"><svg#', $out);
        $this->assertStringContainsString('<li><a class="btn" data-variant="outline" data-size="icon" aria-current="page" href="/articles?page=5">5</a></li>', $out);
        $this->assertStringContainsString('<li><a class="btn" data-variant="ghost" data-size="icon" href="/articles?page=4">4</a></li>', $out);
        $this->assertStringContainsString('<li><a class="btn" data-variant="ghost" data-size="icon" href="/articles?page=10">10</a></li>', $out);
        $this->assertMatchesRegularExpression('#<div class="flex size-9 items-center justify-center" aria-hidden="true"><svg#', $out);
        $this->assertStringContainsString('rel="next" href="/articles?page=6"', $out);
    }

    public function testCoreTemplatesAreRestored(): void
    {
        $this->paginate(1, 3);
        $pagination = new PaginationHelper($this->View);
        $pagination->render();
        $this->assertSame('<li><a href="{{url}}">{{text}}</a></li>', $pagination->Paginator->templater()->get('number'));
    }

    public function testDisabledPrevSimpleAndCounter(): void
    {
        $this->paginate(1, 3);
        $pagination = new PaginationHelper($this->View);
        $out = $pagination->simple(['prev' => 'Prev', 'next' => 'Next', 'counter' => true]);
        $this->assertStringContainsString('<li><a class="btn pointer-events-none opacity-50" data-variant="ghost" aria-disabled="true" tabindex="-1">Prev</a></li>', $out);
        $this->assertStringNotContainsString('data-size="icon"', $out);
        $this->assertStringContainsString('<p class="text-sm text-muted-foreground">Page 1 of 3</p>', $out);
    }
}
