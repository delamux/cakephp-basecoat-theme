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

    public function testMailFolderIcons(): void
    {
        $icon = new IconHelper($this->View);
        // Lucide 0.468.0 markup: Spam (shield-alert) and Drafts (file-pen) in the mail screen.
        $this->assertStringContainsString(
            '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
            $icon->render('shield-alert'),
        );
        $this->assertStringContainsString(
            '<path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/></svg>',
            $icon->render('file-pen'),
        );
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

    public function testBatchThreeIconsRenderDecorative(): void
    {
        $icon = new IconHelper($this->View);
        $names = ['briefcase', 'building-2', 'calendar-clock', 'calendar-days', 'clock', 'dumbbell', 'file-archive', 'file-audio',
            'file-image', 'file-video', 'folder-open', 'globe', 'hard-drive', 'mail-check', 'map-pin', 'rocket', 'upload', 'user-plus'];
        foreach ($names as $name) {
            $this->assertTrue($icon->has($name), $name);
            $out = $icon->render($name);
            $this->assertStringStartsWith('<svg', $out, $name);
            $this->assertStringContainsString('aria-hidden="true"', $out, $name);
        }
        // Lucide 0.468.0 markup.
        $this->assertStringContainsString(
            '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            $icon->render('clock'),
        );
    }

    public function testWorkflowScreenIcons(): void
    {
        $icon = new IconHelper($this->View);
        // Lucide 0.468.0 markup.
        $this->assertStringContainsString(
            '<rect width="8" height="8" x="3" y="3" rx="2"/><path d="M7 11v4a2 2 0 0 0 2 2h4"/><rect width="8" height="8" x="13" y="13" rx="2"/></svg>',
            $icon->render('workflow'),
        );
        $this->assertStringContainsString('<polygon points="6 3 20 12 6 21 6 3"/></svg>', $icon->render('play'));
        $this->assertStringContainsString('aria-hidden="true"', $icon->render('workflow'));
    }

    public function testIconNamesAreAlphabetical(): void
    {
        $property = new \ReflectionProperty(IconHelper::class, 'icons');
        $names = array_keys($property->getValue(new IconHelper($this->View)));
        $sorted = $names;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $names);
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
