<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * KPI card: a label, a big value, the change versus a previous period and an optional sparkline.
 *
 * ```
 * echo $this->MetricCard->render('Active users', '1,284', [
 *     'change' => '+12.4%',
 *     'trend' => 'up',
 *     'description' => 'vs. last month',
 *     'icon' => 'users',
 * ]);
 * ```
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\CardHelper $Card
 * @property \BasecoatTheme\View\Helper\ChartHelper $Chart
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class MetricCardHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Card', 'BasecoatTheme.Chart', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'classes' => [
            'up' => 'text-emerald-600 dark:text-emerald-400',
            'down' => 'text-destructive',
            'flat' => 'text-muted-foreground',
            'top' => 'flex items-center justify-between gap-2',
            'label' => 'text-muted-foreground text-sm font-medium',
            'link' => 'after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none focus-visible:after:ring-[3px] focus-visible:after:ring-ring/50',
            'icon' => 'text-muted-foreground size-4',
            'value' => 'text-2xl font-semibold tabular-nums',
            'change' => 'text-sm font-medium',
            'description' => 'text-muted-foreground text-sm',
            'chart' => 'h-16 w-full',
        ],
        'templates' => [
            'top' => '<div{{attrs}}>{{label}}{{icon}}</div>',
            'label' => '<p{{attrs}}>{{content}}</p>',
            'link' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
            'value' => '<p{{attrs}}>{{content}}</p>',
            'change' => '<span{{attrs}}><span class="sr-only">{{srLabel}}: </span>{{content}}</span>',
            'description' => '<span{{attrs}}>{{content}}</span>',
        ],
    ];

    /**
     * Renders a metric card.
     *
     * ### Options
     *
     * - `change`: Change versus the previous period, e.g. `+12.4%`.
     * - `trend`: `up`, `down` or `flat` (default). Drives the change color and `data-trend`.
     * - `description`: Footer text, e.g. "vs. last month".
     * - `icon`: Icon name or raw SVG shown next to the label.
     * - `chart`: `array{data: iterable, series: array, type?: string, labelKey?: string}`,
     *   rendered as a sparkline.
     * - `url`: The whole card links to this URL.
     * - `escape`: Escape label, value, change and description (default true).
     * - `class` and any HTML attribute for the card.
     *
     * @param string $label Label.
     * @param string $value Value.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $label, string $value, array $options = []): string
    {
        $escape = (bool)($options['escape'] ?? true);
        $trend = in_array($options['trend'] ?? null, ['up', 'down', 'flat'], true) ? $options['trend'] : 'flat';

        $labelHtml = $this->text($label, $escape);
        if (isset($options['url'])) {
            $labelHtml = $this->renderTemplate(
                'link',
                $this->partAttributes('link'),
                ['url' => $this->Url->build($options['url']), 'content' => $labelHtml],
            );
        }
        $labelHtml = $this->renderTemplate('label', $this->partAttributes('label'), ['content' => $labelHtml]);

        $icon = '';
        if (!empty($options['icon'])) {
            $icon = $this->Icon->render((string)$options['icon'], ['class' => $this->getConfig('classes.icon')]);
        }

        $body = $this->renderTemplate('top', $this->partAttributes('top'), ['label' => $labelHtml, 'icon' => $icon]);
        $body .= $this->renderTemplate(
            'value',
            $this->partAttributes('value', ['class' => 'mt-2']),
            ['content' => $this->text($value, $escape)],
        );

        $meta = '';
        if (($options['change'] ?? '') !== '') {
            $meta .= $this->renderTemplate(
                'change',
                $this->partAttributes('change', ['class' => $this->getConfig('classes.' . $trend)]),
                ['srLabel' => h($this->trendLabel($trend)), 'content' => $this->text((string)$options['change'], $escape)],
            );
        }
        if (($options['description'] ?? '') !== '') {
            $meta .= $this->renderTemplate(
                'description',
                $this->partAttributes('description'),
                ['content' => $this->text((string)$options['description'], $escape)],
            );
        }
        if ($meta !== '') {
            $body .= '<div class="mt-1 flex flex-wrap items-center gap-x-2">' . $meta . '</div>';
        }

        if (!empty($options['chart']) && is_array($options['chart'])) {
            $chart = $options['chart'];
            $chartOptions = ['class' => $this->getConfig('classes.chart')];
            foreach (['type', 'labelKey'] as $key) {
                if (isset($chart[$key])) {
                    $chartOptions[$key] = $chart[$key];
                }
            }
            $body .= '<div class="mt-4">'
                . $this->Chart->render($chart['data'] ?? [], (array)($chart['series'] ?? []), $chartOptions)
                . '</div>';
        }

        $cardOptions = $this->stripOptions($options, ['change', 'trend', 'description', 'icon', 'chart', 'url']);
        $cardOptions['data-trend'] = $trend;
        if (isset($options['url'])) {
            $cardOptions['class'] = $this->mergeClasses('relative', $this->getConfig('class'), $options['class'] ?? null);
        } elseif ($this->getConfig('class') !== null) {
            $cardOptions['class'] = $this->mergeClasses($this->getConfig('class'), $options['class'] ?? null);
        }
        $cardOptions += (array)$this->getConfig('attributes');

        return $this->Card->render($body, $cardOptions + ['templateVars' => $options['templateVars'] ?? []]);
    }

    /**
     * Screen reader text for a trend.
     *
     * @param string $trend Trend.
     * @return string
     */
    protected function trendLabel(string $trend): string
    {
        return match ($trend) {
            'up' => __d('basecoat_theme', 'Increase'),
            'down' => __d('basecoat_theme', 'Decrease'),
            default => __d('basecoat_theme', 'No change'),
        };
    }
}
