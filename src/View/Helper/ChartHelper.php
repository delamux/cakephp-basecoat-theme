<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Chart (`.chart` + `<canvas>`), powered by Chart.js and Basecoat's `chart.js`.
 *
 * The canvas carries its config in `data-chart-*` attributes and is initialized by the
 * plugin script `BasecoatTheme.basecoat-theme.js` (loaded by ThemeHelper::scripts()).
 *
 * ```
 * echo $this->Chart->render(
 *     [['month' => 'Sep', 'web' => 431, 'api' => 241], ['month' => 'Oct', 'web' => 509, 'api' => 276]],
 *     [
 *         'web' => ['label' => 'Dashboard signups', 'color' => 'var(--chart-1)'],
 *         'api' => ['label' => 'API signups', 'color' => 'var(--chart-2)'],
 *     ],
 *     ['type' => 'bar', 'labelKey' => 'month', 'class' => 'h-[250px] w-full'],
 * );
 * ```
 */
class ChartHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'type' => 'bar',
        'labelKey' => 'label',
        'legend' => null,
        'class' => 'aspect-auto',
        'attributes' => [],
        'jsonFlags' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        'classes' => [
            'canvas' => null,
        ],
        'templates' => [
            'chart' => '<div{{attrs}}><canvas{{canvasAttrs}}></canvas></div>',
        ],
    ];

    /**
     * Number of charts rendered (used by ThemeHelper to load Chart.js on demand).
     *
     * @var int
     */
    protected int $rendered = 0;

    /**
     * Number of charts rendered by this helper instance.
     *
     * @return int
     */
    public function renderedCount(): int
    {
        return $this->rendered;
    }

    /**
     * Renders a chart.
     *
     * ### Options
     *
     * - `type`: Chart.js type (bar, line, area, pie, doughnut, radar...).
     * - `labelKey`: Key of each row used as label (default `label`).
     * - `legend`: Show the Basecoat legend (bool).
     * - `chartOptions`: Chart.js `options` (array, JSON encoded).
     * - `id`: Canvas id.
     * - `canvasOptions`: Attributes of the `<canvas>` (e.g. `aria-label`).
     * - `class` and any attribute for the `.chart` container.
     *
     * @param iterable<array<string, mixed>> $data Rows.
     * @param array<string, array<string, mixed>> $series Series config keyed by data key.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(iterable $data, array $series = [], array $options = []): string
    {
        $this->rendered++;
        $flags = (int)$this->getConfig('jsonFlags');
        $rows = is_array($data) ? array_values($data) : iterator_to_array($data, false);
        $legend = $options['legend'] ?? $this->getConfig('legend');

        $canvas = (array)($options['canvasOptions'] ?? []) + [
            'id' => $options['id'] ?? null,
            'role' => 'img',
            'data-basecoat-chart' => 'true',
            'data-chart-type' => $options['type'] ?? $this->getConfig('type'),
            'data-chart-label-key' => $options['labelKey'] ?? $this->getConfig('labelKey'),
            'data-chart-legend' => $legend === null ? null : $this->bool((bool)$legend),
            'data-chart-data' => json_encode($rows, $flags),
            'data-chart-series' => $series ? json_encode($series, $flags) : null,
            'data-chart-options' => !empty($options['chartOptions']) ? json_encode($options['chartOptions'], $flags) : null,
        ];

        $attributes = $this->rootAttributes('chart', $options, [
            'type', 'labelKey', 'legend', 'chartOptions', 'id', 'canvasOptions',
        ]);

        return $this->formatTemplate('chart', [
            'attrs' => $this->formatAttributes($attributes),
            'canvasAttrs' => $this->formatAttributes($this->partAttributes('canvas', $canvas)),
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }
}
