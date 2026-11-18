<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Badge (`.badge`).
 *
 * ```
 * echo $this->Badge->render('New');
 * echo $this->Badge->render('+12.5%', ['variant' => 'outline', 'icon' => 'trending-up']);
 * echo $this->Badge->render('Docs', ['url' => '/docs', 'variant' => 'link']);
 * ```
 *
 * Variants: primary (default), secondary, outline, destructive, ghost, link.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class BadgeHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'variant' => null,
        'class' => null,
        'attributes' => [],
        'tag' => 'span',
        'classes' => [
            'icon' => null,
        ],
        'templates' => [
            'badge' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
            'link' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
        ],
    ];

    /**
     * Renders a badge.
     *
     * ### Options
     *
     * - `variant`: Badge variant.
     * - `icon`: Icon name or raw SVG.
     * - `iconPosition`: `start` (default) or `end`.
     * - `iconOptions`: Options for the icon.
     * - `url`: Renders an `<a>` badge.
     * - `tag`: Element name (default config `tag`, `span`).
     * - `escape`: Escape `$text` (default true).
     *
     * @param string $text Badge text.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $text, array $options = []): string
    {
        $content = $this->text($text, $options['escape'] ?? true);
        if (!empty($options['icon'])) {
            $iconOptions = (array)($options['iconOptions'] ?? []);
            $iconOptions['class'] = $this->mergeClasses($this->getConfig('classes.icon'), $iconOptions['class'] ?? null);
            $icon = $this->Icon->render((string)$options['icon'], array_filter($iconOptions));
            $content = ($options['iconPosition'] ?? 'start') === 'end' ? $content . ' ' . $icon : $icon . ' ' . $content;
        }
        $attributes = $this->rootAttributes('badge', $options, ['icon', 'iconPosition', 'iconOptions', 'url', 'tag']);
        $templateVars = $options['templateVars'] ?? [];

        if (isset($options['url'])) {
            return $this->renderTemplate(
                'link',
                $attributes,
                ['url' => $this->Url->build($options['url']), 'content' => $content],
                $templateVars,
            );
        }

        return $this->renderTemplate(
            'badge',
            $attributes,
            ['tag' => $options['tag'] ?? $this->getConfig('tag'), 'content' => $content],
            $templateVars,
        );
    }
}
