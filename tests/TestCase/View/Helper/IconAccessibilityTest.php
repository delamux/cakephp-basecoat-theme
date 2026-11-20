<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use ArrayIterator;
use BasecoatTheme\View\Helper\ButtonHelper;
use BasecoatTheme\View\Helper\DialogHelper;
use BasecoatTheme\View\Helper\DropdownMenuHelper;
use BasecoatTheme\View\Helper\IconHelper;
use BasecoatTheme\View\Helper\PaginationHelper;
use BasecoatTheme\View\Helper\SidebarHelper;
use BasecoatTheme\View\Helper\ThemeHelper;
use Cake\Datasource\Paging\PaginatedResultSet;

/**
 * Icon accessibility (https://lucide.dev/how-to/accessibility):
 * decorative icons are hidden, icon-only controls are named on the control with
 * visually hidden text, and meaning never relies on the icon alone.
 */
class IconAccessibilityTest extends HelperTestCase
{
    public function testIconButtonIsNamedWithVisuallyHiddenText(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->icon('x', 'Close', ['variant' => 'ghost']);
        $this->assertMatchesRegularExpression(
            '#^<button type="button" class="btn" data-variant="ghost" data-size="icon"><svg[^>]*aria-hidden="true"[^>]*>.*</svg><span class="sr-only">Close</span></button>$#',
            $out,
        );
        $this->assertStringNotContainsString('aria-label', $out);

        $link = $button->icon('house', 'Home', ['url' => '/']);
        $this->assertMatchesRegularExpression('#^<a href="/" class="btn" data-size="icon"><svg.*</svg><span class="sr-only">Home</span></a>$#', $link);
        $this->assertStringNotContainsString('aria-label', $link);
    }

    public function testIconButtonLabelIsEscaped(): void
    {
        $out = (new ButtonHelper($this->View))->icon('x', 'Close <b>', ['escape' => false]);
        $this->assertStringContainsString('<span class="sr-only">Close &lt;b&gt;</span>', $out);
    }

    public function testIconButtonIndicatorIsPartOfTheName(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->icon('bell', 'Notifications', ['indicator' => 3]);
        $this->assertStringContainsString('<span class="sr-only">Notifications, 3 new</span>', $out);
        $this->assertSame(1, substr_count($out, 'sr-only'), 'One name, no second screen reader text');
        $this->assertStringContainsString('aria-hidden="true">3</span>', $out);

        $out = $button->icon('mail', 'Inbox', ['indicator' => 3, 'indicatorLabel' => '3 unread messages']);
        $this->assertStringContainsString('<span class="sr-only">Inbox, 3 unread messages</span>', $out);
    }

    public function testExplicitAriaLabelIsKept(): void
    {
        $out = (new ButtonHelper($this->View))->icon('x', 'Close', ['aria-label' => 'Close panel']);
        $this->assertStringContainsString('aria-label="Close panel"', $out);
        $this->assertStringContainsString('<span class="sr-only">Close</span>', $out);
    }

    public function testDecorativeIconsNextToTextStayHidden(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertMatchesRegularExpression('#<svg[^>]*aria-hidden="true"[^>]*>.*</svg>Delete#', $button->render('Delete', ['icon' => 'trash']));
        $this->assertStringNotContainsString('sr-only', $button->render('Delete', ['icon' => 'trash']));
    }

    public function testDropdownTriggerLabel(): void
    {
        $out = (new DropdownMenuHelper($this->View))->render('', ['Edit'], [
            'id' => 'row',
            'trigger' => ['icon' => 'ellipsis-vertical', 'size' => 'icon-sm', 'label' => 'Actions for <Harbor & Pine>'],
        ]);
        $this->assertMatchesRegularExpression(
            '#<button [^>]*id="row-trigger"[^>]*><svg[^>]*aria-hidden="true"[^>]*>.*?</svg><span class="sr-only">Actions for &lt;Harbor &amp; Pine&gt;</span></button>#',
            $out,
        );
        $this->assertStringNotContainsString('label=', $out);
    }

    public function testSidebarToggleIsNamedWithVisuallyHiddenText(): void
    {
        $out = (new SidebarHelper($this->View))->toggle(['label' => 'Menu']);
        $this->assertStringContainsString('<span class="sr-only">Menu</span>', $out);
        $this->assertStringNotContainsString('aria-label', $out);
    }

    public function testThemeToggleIsNamedWithVisuallyHiddenText(): void
    {
        $out = (new ThemeHelper($this->View))->toggle(['label' => 'Dark mode']);
        $this->assertStringContainsString('<span class="sr-only">Dark mode</span>', $out);
        $this->assertStringNotContainsString('aria-label', $out);
        $this->assertSame(2, substr_count($out, 'aria-hidden="true"'), 'Sun and moon are decorative');
    }

    public function testDialogCloseIsNamedWithVisuallyHiddenText(): void
    {
        $out = (new DialogHelper($this->View))->render('d', 'x', ['title' => 'T']);
        $this->assertStringContainsString('<span class="sr-only">Close dialog</span>', $out);
    }

    public function testPaginationIconLinksHaveNames(): void
    {
        $this->View->set('articles', new PaginatedResultSet(new ArrayIterator([]), [
            'count' => 10, 'currentPage' => 5, 'perPage' => 10, 'totalCount' => 100,
            'pageCount' => 10, 'hasPrevPage' => true, 'hasNextPage' => true,
            'sort' => null, 'direction' => null, 'sortDefault' => false, 'directionDefault' => false,
            'completeSort' => [], 'alias' => 'Articles', 'scope' => null, 'limit' => null,
            'start' => 41, 'end' => 50, 'requestedPage' => 5,
        ]));
        $out = (new PaginationHelper($this->View))->render(['first' => true, 'last' => true]);

        // Previous / Next stay announced on small screens (visually hidden, not display:none).
        $this->assertStringContainsString('<span class="sr-only sm:not-sr-only">Previous</span>', $out);
        $this->assertStringContainsString('<span class="sr-only sm:not-sr-only">Next</span>', $out);
        $this->assertStringNotContainsString('hidden sm:block', $out);
        // Icon-only first / last links.
        $this->assertMatchesRegularExpression('#<a [^>]*href="/articles"><svg[^>]*>(?:(?!</svg>).)*</svg><span class="sr-only">First page</span></a>#', $out);
        $this->assertMatchesRegularExpression('#<a [^>]*href="/articles\?page=10"><svg[^>]*>(?:(?!</svg>).)*</svg><span class="sr-only">Last page</span></a>#', $out);
    }

    public function testOneIconPerMeaningIconsExist(): void
    {
        $icon = new IconHelper($this->View);
        foreach (['save', 'app-window'] as $name) {
            $this->assertStringContainsString('<path', $icon->render($name), $name);
        }
    }
}
