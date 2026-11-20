<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\ChartHelper;

class ChartHelperTest extends HelperTestCase
{
    public function testRender(): void
    {
        $chart = new ChartHelper($this->View);
        $this->assertSame(0, $chart->renderedCount());
        $out = $chart->render(
            [['week' => 'Nov 9', 'signups' => 431]],
            ['signups' => ['label' => 'Signups', 'color' => 'var(--chart-1)']],
            ['type' => 'line', 'labelKey' => 'week', 'legend' => true, 'id' => 'signups', 'class' => 'h-[250px] w-full', 'chartOptions' => ['scales' => ['y' => ['beginAtZero' => true]]]],
        );
        $this->assertSame(
            '<div class="chart aspect-auto h-[250px] w-full"><canvas id="signups" role="img" data-basecoat-chart="true" data-chart-type="line" data-chart-label-key="week" data-chart-legend="true"'
            . ' data-chart-data="[{&quot;week&quot;:&quot;Nov 9&quot;,&quot;signups&quot;:431}]"'
            . ' data-chart-series="{&quot;signups&quot;:{&quot;label&quot;:&quot;Signups&quot;,&quot;color&quot;:&quot;var(--chart-1)&quot;}}"'
            . ' data-chart-options="{&quot;scales&quot;:{&quot;y&quot;:{&quot;beginAtZero&quot;:true}}}"></canvas></div>',
            $out,
        );
        $this->assertSame(1, $chart->renderedCount());
    }

    public function testDefaultsFromConfig(): void
    {
        $chart = new ChartHelper($this->View, ['type' => 'bar', 'labelKey' => 'day', 'class' => null]);
        $out = $chart->render([], [], ['canvasOptions' => ['aria-label' => 'Visits']]);
        $this->assertStringStartsWith('<div class="chart"><canvas aria-label="Visits"', $out);
        $this->assertStringContainsString('data-chart-type="bar" data-chart-label-key="day"', $out);
        $this->assertStringNotContainsString('data-chart-series', $out);
    }
}
