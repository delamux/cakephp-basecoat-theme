<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\MetricCardHelper;

class MetricCardHelperTest extends HelperTestCase
{
    public function testRender(): void
    {
        $card = new MetricCardHelper($this->View);
        $out = $card->render('Active users', '1,284', [
            'change' => '+12.4%',
            'trend' => 'up',
            'description' => 'vs. last month',
            'icon' => 'users',
        ]);
        $this->assertStringContainsString('class="card', $out);
        $this->assertStringContainsString('data-trend="up"', $out);
        $this->assertStringContainsString('Active users', $out);
        $this->assertStringContainsString('1,284', $out);
        $this->assertStringContainsString('vs. last month', $out);
        $this->assertStringContainsString('<svg', $out);
        $this->assertMatchesRegularExpression(
            '#<span class="[^"]*text-emerald-600 dark:text-emerald-400[^"]*"><span class="sr-only">Increase: </span>\+12\.4%</span>#',
            $out,
        );
    }

    public function testTrendDownAndFlat(): void
    {
        $card = new MetricCardHelper($this->View);
        $down = $card->render('Churn', '3%', ['change' => '-1%', 'trend' => 'down']);
        $this->assertStringContainsString('text-destructive', $down);
        $this->assertStringContainsString('Decrease', $down);
        $flat = $card->render('Churn', '3%', ['change' => '0%']);
        $this->assertStringContainsString('data-trend="flat"', $flat);
        $this->assertStringContainsString('text-muted-foreground', $flat);
        $this->assertStringContainsString('No change', $flat);
    }

    public function testChartSparkline(): void
    {
        $card = new MetricCardHelper($this->View);
        $out = $card->render('Revenue', '€8,420', ['chart' => [
            'data' => [['d' => 1, 'v' => 3], ['d' => 2, 'v' => 5]],
            'series' => ['v' => ['label' => 'Revenue']],
            'type' => 'line',
            'labelKey' => 'd',
        ]]);
        $this->assertStringContainsString('<canvas', $out);
        $this->assertStringContainsString('h-16 w-full', $out);
        $this->assertStringContainsString('data-chart-type="line"', $out);
    }

    public function testUrlLinksCard(): void
    {
        $card = new MetricCardHelper($this->View);
        $out = $card->render('Users', '5', ['url' => '/users']);
        $this->assertStringContainsString('<a href="/users"', $out);
        $this->assertMatchesRegularExpression('#class="card relative"#', $out);
        $this->assertStringContainsString('after:absolute after:inset-0', $out);
        $this->assertStringContainsString('focus-visible:after:ring-[3px]', $out);
    }

    public function testEscapes(): void
    {
        $card = new MetricCardHelper($this->View);
        $out = $card->render('A <b>', '1 <i>', ['change' => '<u>', 'description' => '<s>']);
        $this->assertStringContainsString('A &lt;b&gt;', $out);
        $this->assertStringNotContainsString('<b>', $out);
        $this->assertStringNotContainsString('<i>', $out);
        $this->assertStringNotContainsString('<u>', $out);
        $this->assertStringNotContainsString('<s>', $out);
    }

    public function testEmpty(): void
    {
        $card = new MetricCardHelper($this->View);
        $out = $card->render('Users', '0');
        $this->assertStringNotContainsString('sr-only', $out);
        $this->assertStringNotContainsString('<canvas', $out);
        $this->assertStringContainsString('data-trend="flat"', $out);
    }
}
