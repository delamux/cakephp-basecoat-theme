<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\CommandMenuHelper;

class CommandMenuHelperTest extends HelperTestCase
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function menuGroups(): array
    {
        return [
            'Actions' => [
                ['label' => 'New project', 'url' => '/projects/add', 'icon' => 'plus', 'shortcut' => 'N', 'keywords' => 'create'],
                ['label' => 'Toggle theme', 'shortcut' => ['Ctrl', 'T']],
            ],
            'Account' => [
                ['label' => 'Settings', 'url' => ['controller' => 'Settings', 'action' => 'index']],
            ],
        ];
    }

    public function testRender(): void
    {
        $helper = new CommandMenuHelper($this->View);
        $html = $this->squash($helper->render($this->menuGroups(), ['id' => 'cm']));

        $this->assertStringStartsWith('<dialog', $html);
        $this->assertStringContainsString('id="cm"', $html);
        $this->assertStringContainsString('class="command-dialog"', $html);
        $this->assertStringContainsString('data-command-hotkey="k"', $html);
        $this->assertStringContainsString('<div class="command">', $html);
        $this->assertStringContainsString('placeholder="Type a command or search…"', $html);
        $this->assertStringContainsString('aria-label="Type a command or search…"', $html, 'Input has an accessible name');
        $this->assertStringContainsString('role="combobox"', $html);
        $this->assertStringContainsString('aria-controls="cm-menu"', $html);
        $this->assertStringContainsString('data-empty="No results found."', $html);
        $this->assertStringContainsString('role="heading"', $html);
        $this->assertStringContainsString('>Actions</span>', $html);
        $this->assertStringContainsString('>Account</span>', $html);
        $this->assertMatchesRegularExpression('#<a href="/projects/add"[^>]*role="menuitem"[^>]*data-filter="New project"[^>]*data-keywords="create"#', $html);
        $this->assertStringContainsString('<span>New project</span>', $html);
        $this->assertStringContainsString('<span data-shortcut>N</span>', $html);
        $this->assertStringContainsString('<span data-shortcut>Ctrl+T</span>', $html);
        $this->assertMatchesRegularExpression('#<div[^>]*role="menuitem"[^>]*data-filter="Toggle theme"#', $html);
        $this->assertStringContainsString('href="/settings"', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testTrigger(): void
    {
        $helper = new CommandMenuHelper($this->View);
        $html = $helper->trigger('cm');

        $this->assertStringContainsString('data-command-open="cm"', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('Search…', $html);
        $this->assertStringContainsString('class="btn ', $html);
        $this->assertStringNotContainsString('command-dialog', $html);
        $this->assertStringContainsString('<kbd', $html);
        $this->assertStringContainsString('⌘K', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testUniqueIds(): void
    {
        $helper = new CommandMenuHelper($this->View);
        $this->assertStringContainsString('id="command-menu-1"', $helper->render($this->menuGroups()));
        $this->assertStringContainsString('id="command-menu-2"', $helper->render($this->menuGroups()));
    }

    public function testEscapes(): void
    {
        $helper = new CommandMenuHelper($this->View);
        $html = $helper->render(['<b>G</b>' => [['label' => '<i>x</i>', 'keywords' => '"q"']]], ['placeholder' => '<p>']);

        $this->assertStringNotContainsString('<b>G', $html);
        $this->assertStringNotContainsString('<i>x', $html);
        $this->assertStringContainsString('&lt;b&gt;G&lt;/b&gt;', $html);
        $this->assertStringContainsString('&lt;i&gt;x&lt;/i&gt;', $html);
        $this->assertStringContainsString('placeholder="&lt;p&gt;"', $html);
        $this->assertStringContainsString('aria-label="&lt;p&gt;"', $html);
        $this->assertStringContainsString('&quot;q&quot;', $html);
    }

    public function testEmpty(): void
    {
        $helper = new CommandMenuHelper($this->View);
        $html = $helper->render([], ['id' => 'cm', 'empty' => 'Nothing here', 'hotkey' => 'j']);

        $this->assertStringContainsString('<dialog', $html);
        $this->assertStringContainsString('data-empty="Nothing here"', $html);
        $this->assertStringContainsString('data-command-hotkey="j"', $html);
        $this->assertStringNotContainsString('role="group"', $html);
    }

    public function testItemUrlWithQueryIsEscapedOnce(): void
    {
        $html = (new CommandMenuHelper($this->View))->render([
            'Pages' => [
                ['label' => 'Paid orders', 'url' => '/orders?status=paid&sort=total'],
                ['label' => 'Failed orders', 'url' => ['controller' => 'Orders', 'action' => 'index', '?' => ['status' => 'failed', 'page' => 2]]],
            ],
        ]);

        $this->assertStringContainsString('href="/orders?status=paid&amp;sort=total"', $html);
        $this->assertStringContainsString('href="/orders?status=failed&amp;page=2"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }
}
