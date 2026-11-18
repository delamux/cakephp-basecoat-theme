<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\CardHelper;
use LogicException;

class CardHelperTest extends HelperTestCase
{
    public function testRenderAllParts(): void
    {
        $card = new CardHelper($this->View);
        $this->assertSame(
            '<div class="card w-full"><header><h2>Total Revenue</h2><p>Last 6 months</p><div class="card-action"><span>+12%</span></div></header>'
            . '<section><p>$1,250.00</p></section><footer class="text-sm">Trending up</footer></div>',
            $card->render('<p>$1,250.00</p>', [
                'title' => 'Total Revenue',
                'description' => 'Last 6 months',
                'action' => '<span>+12%</span>',
                'footer' => 'Trending up',
                'footerOptions' => ['class' => 'text-sm'],
                'class' => 'w-full',
            ]),
        );
    }

    public function testRenderWithoutHeaderAndBody(): void
    {
        $card = new CardHelper($this->View);
        $this->assertSame('<div class="card"></div>', $card->render());
        $this->assertSame('<div class="card"><section>x</section></div>', $card->render('x'));
    }

    public function testConfigClassesAndTitleTag(): void
    {
        $card = new CardHelper($this->View, ['class' => 'shadow-xs', 'titleTag' => 'h3', 'classes' => ['body' => 'px-2']]);
        $this->assertSame(
            '<div class="card shadow-xs"><header><h3>A &amp; B</h3></header><section class="px-2">x</section></div>',
            $card->render('x', ['title' => 'A & B']),
        );
    }

    public function testStartEnd(): void
    {
        $card = new CardHelper($this->View);
        $html = $card->start(['title' => 'Team', 'id' => 'team']) . '<ul></ul>' . $card->end(['footer' => 'F']);
        $this->assertSame('<div id="team" class="card"><header><h2>Team</h2></header><section><ul></ul></section><footer>F</footer></div>', $html);
    }

    public function testEndWithoutStartThrows(): void
    {
        $this->expectException(LogicException::class);
        (new CardHelper($this->View))->end();
    }
}
