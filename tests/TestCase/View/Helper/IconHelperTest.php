<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\IconHelper;
use InvalidArgumentException;

class IconHelperTest extends HelperTestCase
{
    public function testRenderDefault(): void
    {
        $icon = new IconHelper($this->View);
        $this->assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4"><path d="M20 6 9 17l-5-5"/></svg>',
            $icon->render('check'),
        );
    }

    public function testAppendAndReplaceClass(): void
    {
        $icon = new IconHelper($this->View);
        $this->assertStringContainsString('class="size-4 animate-spin"', $icon->render('loader', ['class' => 'animate-spin']));
        $this->assertStringContainsString('class="size-3.5"', $icon->render('loader', ['class' => 'size-3.5', 'replaceClass' => true]));
    }

    public function testConfigurableDefaultsAndCustomIcons(): void
    {
        $icon = new IconHelper($this->View, ['class' => 'size-5', 'icons' => ['rocket' => '<path d="M8 22h8"/>']]);
        $out = $icon->render('rocket', ['data-test' => 'x']);
        $this->assertStringContainsString('class="size-5"', $out);
        $this->assertStringContainsString('data-test="x"', $out);
        $this->assertStringContainsString('<path d="M8 22h8"/>', $out);

        $icon->add(['spark' => '<circle r="1"/>']);
        $this->assertTrue($icon->has('spark'));
        $this->assertTrue($icon->has('rocket'));
        $this->assertContains('spark', $icon->names());
    }

    public function testTitleMakesIconAccessible(): void
    {
        $out = (new IconHelper($this->View))->render('info', ['title' => 'Info & help']);
        $this->assertStringContainsString('role="img"', $out);
        $this->assertStringNotContainsString('aria-hidden', $out);
        $this->assertStringContainsString('<title>Info &amp; help</title>', $out);
    }

    public function testBatchTwoIconsRenderDecorative(): void
    {
        $icon = new IconHelper($this->View);
        $names = ['message-circle', 'sparkles', 'list-checks', 'sticky-note', 'star', 'archive', 'paperclip', 'smile',
            'mic', 'phone', 'video', 'reply', 'forward', 'tag', 'layout-grid', 'image', 'flag', 'users-round'];
        foreach ($names as $name) {
            $out = $icon->render($name);
            $this->assertStringStartsWith('<svg', $out, $name);
            $this->assertStringContainsString('aria-hidden="true"', $out, $name);
        }
    }

    public function testRawMarkupPassThrough(): void
    {
        $svg = '<svg><circle/></svg>';
        $this->assertSame($svg, (new IconHelper($this->View))->render($svg));
        $this->assertSame('', (new IconHelper($this->View))->optional(null));
    }

    public function testDashboardIconsAreBuiltIn(): void
    {
        $icon = new IconHelper($this->View);
        foreach (['calendar', 'download', 'landmark', 'send', 'wallet'] as $name) {
            $this->assertTrue($icon->has($name), $name);
            $this->assertStringStartsWith('<svg', $icon->render($name));
        }
    }

    public function testUnknownIconThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new IconHelper($this->View))->render('nope');
    }

    public function testCustomTemplate(): void
    {
        $icon = new IconHelper($this->View, ['templates' => ['icon' => '<i{{attrs}}>{{content}}</i>']]);
        $this->assertStringStartsWith('<i ', $icon->render('x'));
    }
}
