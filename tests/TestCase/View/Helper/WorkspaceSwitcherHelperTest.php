<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\WorkspaceSwitcherHelper;

class WorkspaceSwitcherHelperTest extends HelperTestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function workspaces(): array
    {
        return [
            ['name' => 'Northwind Labs', 'plan' => 'Pro', 'url' => '/w/northwind'],
            ['name' => 'Bluebird Studio', 'plan' => 'Free', 'url' => '/w/bluebird', 'icon' => 'plus'],
        ];
    }

    public function testRenderCurrent(): void
    {
        $html = (new WorkspaceSwitcherHelper($this->View))->render($this->workspaces());

        $this->assertStringContainsString('class="dropdown-menu', $html);
        $this->assertMatchesRegularExpression('#<button[^>]*aria-haspopup="menu"[^>]*>.*Northwind Labs.*Pro.*</button>#s', $html);
        $this->assertStringContainsString('>Workspaces<', $html);
        $this->assertStringContainsString('Bluebird Studio', $html);
        $this->assertStringContainsString('href="/w/bluebird"', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testCurrentFlag(): void
    {
        $workspaces = $this->workspaces();
        $workspaces[1]['current'] = true;
        $html = (new WorkspaceSwitcherHelper($this->View))->render($workspaces);

        $this->assertMatchesRegularExpression('#<button[^>]*>.*Bluebird Studio.*Free.*</button>#s', $html);
        $this->assertSame(1, substr_count($html, 'aria-checked="true"'));
        $this->assertMatchesRegularExpression('#aria-checked="true"[^>]*>.*?Bluebird#s', $html);
    }

    public function testFirstIsCurrentByDefault(): void
    {
        $html = (new WorkspaceSwitcherHelper($this->View))->render($this->workspaces());

        $this->assertMatchesRegularExpression('#<button[^>]*>.*Northwind Labs.*Pro.*</button>#s', $html);
        $this->assertSame(1, substr_count($html, 'aria-checked="true"'));
    }

    public function testAddAction(): void
    {
        $helper = new WorkspaceSwitcherHelper($this->View);
        $html = $helper->render($this->workspaces(), ['addUrl' => '/workspaces/add']);

        $this->assertStringContainsString('href="/workspaces/add"', $html);
        $this->assertStringContainsString('Add workspace', $html);
        $this->assertStringContainsString('role="separator"', $html);

        $html = $helper->render($this->workspaces(), ['addUrl' => '/x', 'addLabel' => 'New one', 'label' => 'Teams']);
        $this->assertStringContainsString('New one', $html);
        $this->assertStringContainsString('>Teams<', $html);
        $this->assertStringNotContainsString('Add workspace', $helper->render($this->workspaces()));
    }

    public function testEscapes(): void
    {
        $html = (new WorkspaceSwitcherHelper($this->View))->render([
            ['name' => '<script>x</script>', 'plan' => '<b>Pro</b>'],
        ], ['addUrl' => '/a', 'addLabel' => '<i>add</i>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Pro', $html);
        $this->assertStringNotContainsString('<i>add', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testEmpty(): void
    {
        $this->assertSame('', (new WorkspaceSwitcherHelper($this->View))->render([]));
    }

    public function testInitialsLogoIsHiddenNextToTheName(): void
    {
        $html = (new WorkspaceSwitcherHelper($this->View))->render([['name' => 'Lantern Books']]);
        $this->assertStringContainsString('<span aria-hidden="true">LB</span>', $html);
    }
}
