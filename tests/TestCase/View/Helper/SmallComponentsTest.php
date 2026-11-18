<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\AccordionHelper;
use BasecoatTheme\View\Helper\BreadcrumbHelper;
use BasecoatTheme\View\Helper\ButtonGroupHelper;
use BasecoatTheme\View\Helper\EmptyStateHelper;
use BasecoatTheme\View\Helper\KbdHelper;
use BasecoatTheme\View\Helper\ProgressHelper;
use BasecoatTheme\View\Helper\SkeletonHelper;
use BasecoatTheme\View\Helper\TooltipHelper;

/**
 * Accordion, Breadcrumb, ButtonGroup, EmptyState, Kbd, Progress, Skeleton and Tooltip.
 */
class SmallComponentsTest extends HelperTestCase
{
    public function testAccordion(): void
    {
        $accordion = new AccordionHelper($this->View, ['icon' => false]);
        $this->assertSame(
            '<div data-multiple="true" class="accordion"><details><summary>Q &amp; A</summary><div><p>Yes</p></div></details>'
            . '<details open="open" aria-disabled="true"><summary>Locked</summary><div>x</div></details></div>',
            $accordion->render([
                'Q & A' => '<p>Yes</p>',
                ['title' => 'Locked', 'content' => 'x', 'open' => true, 'disabled' => true],
            ], ['multiple' => true]),
        );
        $this->assertMatchesRegularExpression('#<summary>Q<svg#', (new AccordionHelper($this->View))->render(['Q' => 'A']));
    }

    public function testBreadcrumb(): void
    {
        $breadcrumb = new BreadcrumbHelper($this->View, ['separator' => '<span>/</span>']);
        $this->assertSame(
            '<nav aria-label="Breadcrumb" class="breadcrumb"><ol>'
            . '<li><a href="/">Home</a></li><li aria-hidden="true"><span>/</span></li>'
            . '<li><a href="/articles">Articles</a></li><li aria-hidden="true"><span>/</span></li>'
            . '<li><span aria-current="page">Edit &lt;1&gt;</span></li></ol></nav>',
            $breadcrumb->render(['Home' => '/', 'Articles' => ['controller' => 'Articles', 'action' => 'index'], 'Edit <1>']),
        );
    }

    public function testBreadcrumbCollapse(): void
    {
        $breadcrumb = new BreadcrumbHelper($this->View);
        $out = $breadcrumb->render(['A' => '/a', 'B' => '/b', 'C' => '/c', 'D' => '/d', 'E'], ['collapse' => 3]);
        $this->assertStringContainsString('data-collapsed="true"', $out);
        $this->assertStringContainsString('>A</a>', $out);
        $this->assertStringNotContainsString('>B</a>', $out);
        $this->assertStringNotContainsString('>C</a>', $out);
        $this->assertStringContainsString('>D</a>', $out);
        $this->assertStringContainsString('<span aria-current="page">E</span>', $out);
        $this->assertStringContainsString('<li><span aria-hidden="true"><svg', $out);
    }

    public function testButtonGroup(): void
    {
        $group = new ButtonGroupHelper($this->View);
        $this->assertSame(
            '<div aria-label="Range" role="group" data-orientation="vertical" class="button-group">'
            . '<button aria-pressed="true" type="button" class="btn" data-variant="secondary" data-size="sm">3m</button>'
            . '<a href="/d" class="btn" data-variant="outline" data-size="sm">30d</a><b>raw</b></div>',
            $group->render([
                ['text' => '3m', 'variant' => 'secondary', 'aria-pressed' => 'true'],
                ['text' => '30d', 'url' => '/d'],
                '<b>raw</b>',
            ], ['aria-label' => 'Range', 'orientation' => 'vertical', 'button' => ['size' => 'sm']]),
        );
    }

    public function testEmptyState(): void
    {
        $empty = new EmptyStateHelper($this->View);
        $out = $empty->render('No projects', ['description' => 'Scan one', 'icon' => 'inbox', 'actions' => '<a>Scan</a>', 'footer' => 'Help']);
        $this->assertMatchesRegularExpression('#^<div class="empty"><header><figure><svg#', $out);
        $this->assertStringContainsString('</figure><h3>No projects</h3><p>Scan one</p></header><section><a>Scan</a></section><footer>Help</footer></div>', $out);
        $this->assertSame('<div class="empty"><header><h3>Only</h3></header></div>', $empty->render('Only'));
    }

    public function testKbd(): void
    {
        $kbd = new KbdHelper($this->View);
        $this->assertSame('<kbd class="kbd">⌘K</kbd>', $kbd->render('⌘K'));
        $this->assertSame(
            '<span class="inline-flex items-center gap-1"><kbd class="kbd">Ctrl</kbd><span>+</span><kbd class="kbd">B</kbd></span>',
            $kbd->render(['Ctrl', 'B']),
        );
    }

    public function testProgress(): void
    {
        $progress = new ProgressHelper($this->View);
        $this->assertSame(
            '<div role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" class="progress"><span style="width: 60%;"></span></div>',
            $progress->render(60),
        );
        $this->assertStringContainsString('width: 30%;', $progress->render(3, ['max' => 10]));
        $this->assertStringContainsString('width: 100%;', $progress->render(150));
    }

    public function testSkeleton(): void
    {
        $skeleton = new SkeletonHelper($this->View);
        $this->assertSame('<div aria-hidden="true" class="skeleton rounded-md bg-accent h-4 w-20"></div>', $skeleton->render(['class' => 'h-4 w-20']));
        $this->assertSame(3, substr_count($skeleton->lines(3), 'class="skeleton'));
    }

    public function testTooltip(): void
    {
        $tooltip = new TooltipHelper($this->View, ['side' => 'top']);
        $this->assertSame(
            '<span data-tooltip="Add &amp; save" data-side="bottom" data-align="start"><button>x</button></span>',
            $tooltip->render('<button>x</button>', 'Add & save', ['side' => 'bottom', 'align' => 'start']),
        );
        $this->assertSame(['data-tooltip' => 'Hi', 'data-side' => 'top', 'id' => 'b'], $tooltip->attributes('Hi', ['id' => 'b']));
    }
}
