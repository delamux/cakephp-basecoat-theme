<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\AlertHelper;

class AlertHelperTest extends HelperTestCase
{
    public function testRenderTitleAndDescription(): void
    {
        $alert = new AlertHelper($this->View);
        $this->assertSame(
            '<div role="alert" class="alert"><h2>Heads up</h2><section>Be &lt;careful&gt;</section></div>',
            $alert->render('Heads up', 'Be <careful>'),
        );
    }

    public function testDestructiveListIconAndFooter(): void
    {
        $alert = new AlertHelper($this->View);
        $out = $alert->render('Unable to process', ['Check card', 'Verify address'], [
            'variant' => 'destructive',
            'icon' => 'circle-alert',
            'footer' => '<a href="#">Retry</a>',
            'titleTag' => 'h3',
        ]);
        $this->assertStringStartsWith('<div role="alert" class="alert" data-variant="destructive"><svg', $out);
        $this->assertStringContainsString('<h3>Unable to process</h3>', $out);
        $this->assertStringContainsString('<section><ul><li>Check card</li><li>Verify address</li></ul></section>', $out);
        $this->assertStringEndsWith('<footer><a href="#">Retry</a></footer></div>', $out);
    }

    public function testTypeShortcutsUseConfig(): void
    {
        $alert = new AlertHelper($this->View, ['types' => ['error' => ['icon' => null, 'variant' => 'destructive', 'class' => 'mb-4']]]);
        $this->assertSame(
            '<div data-type="error" role="alert" class="alert mb-4" data-variant="destructive"><h2>Oops</h2></div>',
            $alert->error('Oops'),
        );
        $this->assertStringContainsString('data-type="success"', $alert->success('Saved'));
        $this->assertStringContainsString('<svg', $alert->success('Saved'));
    }

    public function testPartClassesFromConfig(): void
    {
        $alert = new AlertHelper($this->View, ['classes' => ['title' => 'font-bold']]);
        $this->assertStringContainsString('<h2 class="font-bold">T</h2>', $alert->render('T'));
    }
}
