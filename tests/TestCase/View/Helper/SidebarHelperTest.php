<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\SidebarHelper;

class SidebarHelperTest extends HelperTestCase
{
    public function testRenderStructureAndCurrentPage(): void
    {
        $sidebar = new SidebarHelper($this->View);
        $out = $this->squash($sidebar->render([
            ['label' => 'Articles', 'url' => ['controller' => 'Articles', 'action' => 'index']],
            ['label' => 'Team', 'url' => '/team'],
            ['type' => 'group', 'label' => 'Documents', 'items' => [
                ['label' => 'Reports', 'url' => '/reports'],
            ]],
        ], ['header' => '<a href="/">Acme</a>', 'footer' => 'F']));

        $this->assertSame(
            '<aside id="sidebar" data-side="left" aria-hidden="false" class="sidebar"><nav aria-label="Sidebar navigation">'
            . '<header><a href="/">Acme</a></header><section class="scrollbar-sm">'
            . '<div role="group"><ul>'
            . '<li><a href="/articles" aria-current="page"><span>Articles</span></a></li>'
            . '<li><a href="/team"><span>Team</span></a></li>'
            . '</ul></div>'
            . '<div role="group" aria-labelledby="sidebar-content-3-label"><h3 id="sidebar-content-3-label">Documents</h3><ul>'
            . '<li><a href="/reports"><span>Reports</span></a></li>'
            . '</ul></div>'
            . '</section><footer>F</footer></nav></aside>',
            $out,
        );
    }

    public function testClosedRightSideIconsAndSubmenuOpensOnCurrent(): void
    {
        $sidebar = new SidebarHelper($this->View, ['id' => 'nav', 'currentMatch' => 'prefix']);
        $out = $sidebar->render([
            ['type' => 'submenu', 'label' => 'Content', 'icon' => 'folder', 'items' => [
                ['label' => 'All', 'url' => '/articles'],
            ]],
            '-',
            ['label' => 'Settings', 'url' => '#', 'icon' => 'settings-2', 'badge' => '<span class="badge">2</span>'],
        ], ['open' => false, 'side' => 'right']);

        $this->assertStringStartsWith('<aside id="nav" data-side="right" aria-hidden="true" inert="inert" class="sidebar">', $out);
        $this->assertMatchesRegularExpression('#<details id="submenu-nav-content-1-1" open="open"><summary aria-controls="submenu-nav-content-1-1-content"><svg#', $out);
        $this->assertStringContainsString('<a href="/articles" aria-current="page">', $out);
        $this->assertStringContainsString('<hr role="separator">', $out);
        $this->assertMatchesRegularExpression('#<a href="\#"><svg.*</svg><span>Settings</span><span class="badge">2</span></a>#', $out);
    }

    public function testToggle(): void
    {
        $sidebar = new SidebarHelper($this->View, ['id' => 'main-nav']);
        $out = $sidebar->toggle(['label' => 'Menu']);
        $this->assertStringContainsString('onclick="document.getElementById(&#039;main-nav&#039;)?.toggle()"', $out);
        $this->assertStringContainsString('aria-label="Menu"', $out);
        $this->assertStringContainsString('data-size="icon-sm"', $out);
    }
}
