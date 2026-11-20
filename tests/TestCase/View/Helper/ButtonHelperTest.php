<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\ButtonHelper;

class ButtonHelperTest extends HelperTestCase
{
    public function testRenderDefault(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertSame('<button type="button" class="btn">Save</button>', $button->render('Save'));
    }

    public function testVariantSizeClassAndAttributes(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertSame(
            '<button type="submit" id="go" class="btn w-full" data-variant="outline" data-size="sm">Go &amp; run</button>',
            $button->render('Go & run', ['type' => 'submit', 'variant' => 'outline', 'size' => 'sm', 'class' => 'w-full', 'id' => 'go']),
        );
    }

    public function testConfigDefaultsCanBeOverriddenPerCall(): void
    {
        $button = new ButtonHelper($this->View, [
            'variant' => 'secondary',
            'size' => 'lg',
            'class' => 'rounded-full',
            'attributes' => ['data-turbo' => 'false'],
        ]);
        $this->assertSame(
            '<button type="button" data-turbo="false" class="btn rounded-full" data-variant="secondary" data-size="lg">A</button>',
            $button->render('A'),
        );
        $this->assertSame(
            '<button type="button" data-turbo="false" class="btn rounded-full" data-variant="ghost">B</button>',
            $button->render('B', ['variant' => 'ghost', 'size' => null]),
        );
    }

    public function testIconPositionAndEscape(): void
    {
        $button = new ButtonHelper($this->View);
        $start = $button->render('<b>Bold</b>', ['icon' => 'plus', 'escape' => false]);
        $this->assertMatchesRegularExpression('#^<button[^>]*><svg[^>]*>.*</svg><b>Bold</b></button>$#', $start);

        $end = $button->render('Next', ['icon' => 'chevron-right', 'iconPosition' => 'end']);
        $this->assertMatchesRegularExpression('#>Next<svg#', $end);
    }

    public function testCollapseHidesLabelBelowBreakpointButKeepsItAccessible(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->link('New user', '/users/add', ['icon' => 'plus', 'collapse' => 'md']);

        $this->assertStringContainsString('<span class="sr-only md:not-sr-only">New user</span>', $out);
        $this->assertStringContainsString('title="New user"', $out);
        $this->assertStringNotContainsString('collapse=', $out);
    }

    public function testCollapseTrueUsesSmallBreakpointAndKeepsCustomTitle(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->render('Export', ['icon' => 'arrow-down', 'collapse' => true, 'title' => 'Export as PDF']);

        $this->assertStringContainsString('<span class="sr-only sm:not-sr-only">Export</span>', $out);
        $this->assertStringContainsString('title="Export as PDF"', $out);
    }

    public function testCollapseWithoutIconIsIgnored(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertSame('<button type="button" class="btn">Save</button>', $button->render('Save', ['collapse' => 'sm']));
    }

    public function testLinkUsesUrlHelper(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertSame(
            '<a href="/articles/view/1" class="btn" data-variant="link">View</a>',
            $button->link('View', ['controller' => 'Articles', 'action' => 'view', 1], ['variant' => 'link']),
        );
        $this->assertStringContainsString('href="http://localhost/about"', $button->link('About', '/about', ['fullBase' => true]));
    }

    public function testIconButton(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->icon('x', 'Close', ['variant' => 'ghost']);
        $this->assertStringStartsWith('<button aria-label="Close" type="button" class="btn" data-variant="ghost" data-size="icon">', $out);
        $this->assertStringStartsWith('<a href="/" aria-label="Home"', $button->icon('house', 'Home', ['url' => '/']));
    }

    public function testIndicatorDot(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->icon('bell', 'Notifications', ['indicator' => true]);
        $this->assertStringContainsString('class="btn relative"', $out);
        $this->assertMatchesRegularExpression('#<span class="[^"]*" aria-hidden="true"></span>#', $out);
        $this->assertStringContainsString('<span class="sr-only">New</span>', $out);
        $this->assertStringNotContainsString('indicator', $out);
    }

    public function testIndicatorCount(): void
    {
        $button = new ButtonHelper($this->View);
        $out = $button->icon('bell', 'Notifications', ['indicator' => 3]);
        $this->assertStringContainsString('>3<', $out);
        $this->assertStringContainsString('<span class="sr-only">3 new</span>', $out);
        $out = $button->render('Inbox', ['indicator' => '9+', 'indicatorLabel' => 'Unread']);
        $this->assertStringContainsString('>9+<', $out);
        $this->assertStringContainsString('<span class="sr-only">Unread</span>', $out);
        $this->assertStringContainsString('<span class="sr-only">New</span>', $button->link('Inbox', '/', ['indicator' => true]));
    }

    public function testIndicatorIsPartOfIconAriaLabel(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertStringContainsString('aria-label="Notifications, 3 new"', $button->icon('bell', 'Notifications', ['indicator' => 3]));
        $this->assertStringContainsString('aria-label="Notifications, New"', $button->icon('bell', 'Notifications', ['indicator' => true]));
    }

    public function testNoIndicatorUnchanged(): void
    {
        $button = new ButtonHelper($this->View);
        $this->assertSame('<button type="button" class="btn">Save</button>', $button->render('Save'));
        $this->assertSame('<button type="button" class="btn">Save</button>', $button->render('Save', ['indicator' => false]));
    }

    public function testSubmitAndCustomTemplate(): void
    {
        $button = new ButtonHelper($this->View, ['templates' => ['button' => '<button{{attrs}}><span>{{content}}</span></button>']]);
        $this->assertSame('<button type="submit" class="btn"><span>Send</span></button>', $button->submit('Send'));

        $button->setTemplates(['button' => '<button{{attrs}} data-x="{{x}}">{{content}}</button>']);
        $this->assertSame(
            '<button type="button" class="btn" data-x="1">A</button>',
            $button->render('A', ['templateVars' => ['x' => '1']]),
        );
    }
}
