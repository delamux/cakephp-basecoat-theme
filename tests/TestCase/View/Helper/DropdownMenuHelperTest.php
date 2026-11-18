<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\DropdownMenuHelper;

class DropdownMenuHelperTest extends HelperTestCase
{
    public function testRenderStructure(): void
    {
        $menu = new DropdownMenuHelper($this->View);
        $out = $menu->render('Open', [
            ['label' => 'Profile', 'url' => '/profile', 'shortcut' => '⇧⌘P'],
            '-',
            ['label' => 'Delete', 'variant' => 'destructive'],
        ], ['id' => 'dm', 'align' => 'end', 'popoverOptions' => ['class' => 'min-w-56']]);

        $this->assertSame(
            '<div id="dm" class="dropdown-menu">'
            . '<button class="btn" type="button" id="dm-trigger" aria-haspopup="menu" aria-controls="dm-menu" aria-expanded="false" data-variant="outline">Open</button>'
            . '<div data-popover class="min-w-56" id="dm-popover" aria-hidden="true" data-align="end">'
            . '<div role="menu" id="dm-menu" aria-labelledby="dm-trigger">'
            . '<a href="/profile" role="menuitem">Profile<span data-shortcut>⇧⌘P</span></a>'
            . '<hr role="separator">'
            . '<div role="menuitem" data-variant="destructive">Delete</div>'
            . '</div></div></div>',
            $menu->render('Open', [
                ['label' => 'Profile', 'url' => '/profile', 'shortcut' => '⇧⌘P'],
                '-',
                ['label' => 'Delete', 'variant' => 'destructive'],
            ], ['id' => 'dm', 'align' => 'end', 'popoverOptions' => ['class' => 'min-w-56']]),
        );
        $this->assertStringContainsString('dm-trigger', $out);
    }

    public function testGroupsCheckboxesAndTriggerOptions(): void
    {
        $menu = new DropdownMenuHelper($this->View, ['trigger' => ['variant' => 'ghost', 'size' => 'icon-sm']]);
        $out = $menu->render('', [
            ['type' => 'group', 'label' => 'Columns', 'items' => [
                ['type' => 'checkbox', 'label' => 'Status', 'checked' => true],
                ['type' => 'checkbox', 'label' => 'Target'],
            ]],
            ['type' => 'radio', 'label' => 'Top', 'checked' => true],
            ['label' => 'Off', 'disabled' => true],
        ], ['id' => 'c', 'trigger' => ['icon' => 'ellipsis-vertical', 'aria-label' => 'Actions']]);

        $this->assertMatchesRegularExpression('#<button aria-label="Actions" class="btn" type="button" id="c-trigger"[^>]*data-variant="ghost" data-size="icon-sm"><svg#', $out);
        $this->assertStringContainsString('<div role="group" aria-labelledby="c-items-1-label"><div role="heading" id="c-items-1-label">Columns</div>', $out);
        $this->assertStringContainsString('<div role="menuitemcheckbox" aria-checked="true">Status</div>', $out);
        $this->assertStringContainsString('<div role="menuitemcheckbox" aria-checked="false">Target</div>', $out);
        $this->assertStringContainsString('<div role="menuitemradio" aria-checked="true">Top</div>', $out);
        $this->assertStringContainsString('<div role="menuitem" aria-disabled="true">Off</div>', $out);
    }

    public function testLabelsAreEscaped(): void
    {
        $menu = new DropdownMenuHelper($this->View);
        $out = $menu->render('<x>', ['<script>'], ['id' => 'e']);
        $this->assertStringContainsString('&lt;x&gt;</button>', $out);
        $this->assertStringContainsString('&lt;script&gt;</div>', $out);
    }
}
