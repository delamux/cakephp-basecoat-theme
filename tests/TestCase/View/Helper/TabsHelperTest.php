<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\TabsHelper;

class TabsHelperTest extends HelperTestCase
{
    public function testRenderAssociative(): void
    {
        $tabs = new TabsHelper($this->View);
        $this->assertSame(
            '<div id="settings" class="tabs"><nav role="tablist" aria-orientation="horizontal">'
            . '<button type="button" role="tab" id="settings-tab-1" aria-controls="settings-panel-1" aria-selected="true" tabindex="0">Account</button>'
            . '<button type="button" role="tab" id="settings-tab-2" aria-controls="settings-panel-2" aria-selected="false" tabindex="-1">Password</button>'
            . '</nav>'
            . '<div role="tabpanel" id="settings-panel-1" aria-labelledby="settings-tab-1" tabindex="-1" aria-selected="true">A</div>'
            . '<div role="tabpanel" id="settings-panel-2" aria-labelledby="settings-tab-2" tabindex="-1" aria-selected="false" hidden="hidden">P</div>'
            . '</div>',
            $tabs->render(['Account' => 'A', 'Password' => 'P'], ['id' => 'settings']),
        );
    }

    public function testActiveByIdVariantOrientationAndHtmlLabel(): void
    {
        $tabs = new TabsHelper($this->View, ['class' => 'w-full']);
        $out = $tabs->render([
            ['tab' => 'Outline', 'id' => 'outline', 'panel' => 'o'],
            ['tab' => 'Past <span class="badge">3</span>', 'id' => 'past', 'panel' => 'p', 'escape' => false],
            ['tab' => 'Disabled', 'disabled' => true],
        ], ['id' => 't', 'active' => 'past', 'variant' => 'line', 'orientation' => 'vertical']);

        $this->assertStringStartsWith('<div id="t" class="tabs w-full"><nav role="tablist" aria-orientation="vertical" data-variant="line">', $out);
        $this->assertStringContainsString('id="t-tab-past" aria-controls="t-panel-past" aria-selected="true" tabindex="0">Past <span class="badge">3</span></button>', $out);
        $this->assertStringContainsString('id="t-panel-outline" aria-labelledby="t-tab-outline" tabindex="-1" aria-selected="false" hidden="hidden"', $out);
        $this->assertStringContainsString('aria-selected="false" tabindex="-1" disabled="disabled">Disabled</button>', $out);
        $this->assertStringNotContainsString('t-panel-3', $out);
    }

    public function testGeneratedIds(): void
    {
        $tabs = new TabsHelper($this->View);
        $this->assertStringContainsString('id="tabs-1"', $tabs->render(['A' => 'a']));
        $this->assertStringContainsString('id="tabs-2"', $tabs->render(['A' => 'a']));
    }
}
