<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\BadgeHelper;

class BadgeHelperTest extends HelperTestCase
{
    public function testRender(): void
    {
        $badge = new BadgeHelper($this->View);
        $this->assertSame('<span class="badge">New</span>', $badge->render('New'));
        $this->assertSame(
            '<span class="badge px-1.5" data-variant="outline">A &lt;b&gt;</span>',
            $badge->render('A <b>', ['variant' => 'outline', 'class' => 'px-1.5']),
        );
    }

    public function testIconLinkTagAndConfig(): void
    {
        $badge = new BadgeHelper($this->View, ['variant' => 'secondary', 'tag' => 'div']);
        $this->assertMatchesRegularExpression('#^<div class="badge" data-variant="secondary"><svg[^>]*>.*</svg> 3</div>$#', $badge->render('3', ['icon' => 'check']));
        $this->assertSame('<a href="/docs" class="badge" data-variant="link">Docs</a>', $badge->render('Docs', ['url' => '/docs', 'variant' => 'link']));
    }
}
