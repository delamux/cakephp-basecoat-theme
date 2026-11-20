<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\DateRangeFieldHelper;
use Cake\Http\ServerRequest;
use Cake\View\View;

class DateRangeFieldHelperTest extends HelperTestCase
{
    public function testRender(): void
    {
        $helper = new DateRangeFieldHelper($this->View);
        $out = $helper->render('created', ['from' => '2026-11-01', 'to' => '2026-11-30', 'id' => 'dr']);

        $this->assertStringContainsString('<div id="dr" class="popover">', $out);
        $this->assertStringContainsString('name="created[from]"', $out);
        $this->assertStringContainsString('name="created[to]"', $out);
        $this->assertStringContainsString('value="2026-11-01"', $out);
        $this->assertStringContainsString('type="date"', $out);
        $this->assertStringContainsString('2026-11-01 – 2026-11-30</span>', $out);
        $this->assertStringContainsString('aria-controls="dr-popover"', $out);
        $this->assertStringContainsString('data-popover', $out);
        $this->assertStringContainsString('<rect width="18" height="18" x="3" y="4" rx="2"/>', $out, 'Built-in calendar icon');
    }

    public function testPlaceholderWhenEmpty(): void
    {
        $helper = new DateRangeFieldHelper($this->View);
        $this->assertStringContainsString('Pick a date range</span>', $helper->render('created'));
    }

    public function testReadsRequestQuery(): void
    {
        $view = new View(new ServerRequest(['url' => '/x', 'query' => ['created' => ['from' => '2026-01-01']]]));
        $out = (new DateRangeFieldHelper($view))->render('created');

        $this->assertStringContainsString('name="created[from]"', $out);
        $this->assertStringContainsString('value="2026-01-01"', $out);
        $this->assertStringContainsString('2026-01-01 – …</span>', $out);
    }

    public function testPresets(): void
    {
        $helper = new DateRangeFieldHelper($this->View);
        $out = $helper->render('created', ['presets' => ['This month' => ['2026-11-01', '2026-11-30']]]);

        $this->assertStringContainsString('data-range-from="2026-11-01"', $out);
        $this->assertStringContainsString('data-range-to="2026-11-30"', $out);
        $this->assertStringContainsString('>This month</button>', $out);
    }

    public function testUniqueIds(): void
    {
        $helper = new DateRangeFieldHelper($this->View);
        $a = $helper->render('a');
        $b = $helper->render('b');

        preg_match_all('/<div id="([^"]+)" class="popover">/', $a . $b, $m);
        $this->assertCount(2, array_unique($m[1]));
        foreach ([$a, $b] as $html) {
            preg_match_all('/<label[^>]* for="([^"]+)"/', $html, $labels);
            $this->assertCount(2, $labels[1]);
            foreach ($labels[1] as $for) {
                $this->assertStringContainsString('id="' . $for . '"', $html);
            }
        }
    }

    public function testEscapes(): void
    {
        $helper = new DateRangeFieldHelper($this->View);
        $out = $helper->render('x', [
            'label' => '<b>L</b>',
            'from' => '"><script>',
            'presets' => ['<i>p</i>' => ['"a', '"b']],
        ]);

        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringNotContainsString('<b>L</b>', $out);
        $this->assertStringNotContainsString('<i>p</i>', $out);
        $this->assertStringContainsString('&lt;b&gt;L&lt;/b&gt;', $out);
    }
}
