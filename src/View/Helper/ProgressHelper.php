<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Progress (`.progress`).
 *
 * ```
 * echo $this->Progress->render(60);
 * echo $this->Progress->render(3, ['max' => 10, 'aria-label' => 'Upload']);
 * ```
 */
class ProgressHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'max' => 100,
        'classes' => [
            'indicator' => null,
        ],
        'templates' => [
            'progress' => '<div{{attrs}}><span{{indicatorAttrs}}></span></div>',
        ],
    ];

    /**
     * Renders a progress bar.
     *
     * ### Options
     *
     * - `max`: Maximum value (default 100).
     * - `indicatorOptions`: Attributes of the inner bar.
     * - `class` and any attribute.
     *
     * @param float|int $value Current value.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(int|float $value, array $options = []): string
    {
        $max = (float)($options['max'] ?? $this->getConfig('max'));
        $percent = $max > 0 ? max(0.0, min(100.0, $value / $max * 100)) : 0.0;
        $percentText = rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');

        // Width applied by webroot/js/basecoat-theme.js (CSSOM): a style attribute would need a CSP
        // with 'unsafe-inline'
        $indicator = (array)($options['indicatorOptions'] ?? []) + ['data-progress-value' => $percentText];

        $options += [
            'role' => 'progressbar',
            'aria-valuenow' => (string)$value,
            'aria-valuemin' => '0',
            'aria-valuemax' => (string)($options['max'] ?? $this->getConfig('max')),
        ];
        $attributes = $this->rootAttributes('progress', $options, ['max', 'indicatorOptions']);

        return $this->formatTemplate('progress', [
            'attrs' => $this->formatAttributes($attributes),
            'indicatorAttrs' => $this->formatAttributes($this->partAttributes('indicator', $indicator)),
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }
}
