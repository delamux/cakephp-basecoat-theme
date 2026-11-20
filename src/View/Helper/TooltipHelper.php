<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Tooltip (CSS-only, `data-tooltip` attribute).
 *
 * ```
 * echo $this->Tooltip->render('<button class="btn">Share</button>', 'Copy a read-only link');
 * echo $this->Button->render('Save', $this->Tooltip->attributes('Save changes', ['side' => 'bottom']));
 * ```
 */
class TooltipHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'side' => null,
        'align' => null,
        'tag' => 'span',
        'templates' => [
            'tooltip' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
        ],
    ];

    /**
     * Wraps `$content` in an element that shows `$text` as tooltip.
     *
     * ### Options
     *
     * - `side`: top (default), bottom, left, right.
     * - `align`: start, center, end.
     * - `tag`: Wrapper tag (default `span`).
     * - `class` and any attribute.
     *
     * @param string $content Trigger HTML (not escaped).
     * @param string $text Tooltip text.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $content, string $text, array $options = []): string
    {
        $attributes = $this->attributes($text, $options);
        $attributes['class'] = $this->mergeClasses($this->getConfig('class'), $options['class'] ?? null);
        $attributes = $this->cleanAttributes(
            $this->stripOptions($attributes + (array)$this->getConfig('attributes'), ['side', 'align', 'tag']),
        );

        return $this->renderTemplate('tooltip', $attributes, [
            'tag' => $options['tag'] ?? $this->getConfig('tag'),
            'content' => $content,
        ], $options['templateVars'] ?? []);
    }

    /**
     * Returns the attributes that add a tooltip to any element.
     *
     * @param string $text Tooltip text.
     * @param array<string, mixed> $options `side`, `align` and other attributes to merge.
     * @return array<string, mixed>
     */
    public function attributes(string $text, array $options = []): array
    {
        $side = $options['side'] ?? $this->getConfig('side');
        $align = $options['align'] ?? $this->getConfig('align');
        unset($options['side'], $options['align']);

        return array_filter([
            'data-tooltip' => $text,
            'data-side' => $side,
            'data-align' => $align,
        ], fn($v) => $v !== null) + $options;
    }
}
