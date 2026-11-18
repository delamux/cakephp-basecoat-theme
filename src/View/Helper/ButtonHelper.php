<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Button (`.btn`).
 *
 * ```
 * echo $this->Button->render('Save', ['type' => 'submit']);
 * echo $this->Button->render('Delete', ['variant' => 'destructive', 'icon' => 'trash']);
 * echo $this->Button->link('Docs', ['controller' => 'Pages', 'action' => 'display', 'docs'], ['variant' => 'link']);
 * echo $this->Button->icon('panel-left', 'Toggle sidebar', ['variant' => 'ghost', 'size' => 'icon-sm']);
 * ```
 *
 * Variants: primary (default), secondary, outline, ghost, link, destructive.
 * Sizes: xs, sm, default, lg, icon, icon-xs, icon-sm, icon-lg.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class ButtonHelper extends AbstractComponentHelper
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
        'size' => null,
        'class' => null,
        'attributes' => [],
        'classes' => [
            'icon' => null,
        ],
        'templates' => [
            'button' => '<button{{attrs}}>{{content}}</button>',
            'link' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
            'text' => '{{text}}',
        ],
    ];

    /**
     * Renders a `<button>`.
     *
     * ### Options
     *
     * - `type`: Button type, `button` by default.
     * - `variant`, `size`: Basecoat variant/size (`data-variant`/`data-size`).
     * - `icon`: Icon name or raw SVG.
     * - `iconPosition`: `start` (default) or `end`.
     * - `iconOptions`: Options for the icon.
     * - `escape`: Escape `$text` (default true).
     * - `class` and any HTML attribute.
     *
     * @param string $text Button text.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $text, array $options = []): string
    {
        $options += ['type' => 'button'];
        $content = $this->content($text, $options);
        $attributes = $this->rootAttributes('btn', $options, ['icon', 'iconPosition', 'iconOptions']);

        return $this->renderTemplate('button', $attributes, ['content' => $content], $options['templateVars'] ?? []);
    }

    /**
     * Renders a submit button.
     *
     * @param string $text Button text.
     * @param array<string, mixed> $options Options, see render().
     * @return string
     */
    public function submit(string $text, array $options = []): string
    {
        return $this->render($text, ['type' => 'submit'] + $options);
    }

    /**
     * Renders an `<a class="btn">`. The URL is built with UrlHelper.
     *
     * ### Options
     *
     * Same as render() plus:
     * - `fullBase`: Build an absolute URL.
     *
     * @param string $text Link text.
     * @param array|string|null $url URL (route array or string).
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function link(string $text, array|string|null $url = null, array $options = []): string
    {
        $content = $this->content($text, $options);
        $href = $this->Url->build($url, ['fullBase' => (bool)($options['fullBase'] ?? false)]);
        $attributes = $this->rootAttributes('btn', $options, ['icon', 'iconPosition', 'iconOptions', 'fullBase', 'href']);

        return $this->renderTemplate(
            'link',
            $attributes,
            ['url' => $href, 'content' => $content],
            $options['templateVars'] ?? [],
        );
    }

    /**
     * Renders an icon-only button. `$label` becomes the `aria-label`.
     *
     * When `url` is given in `$options`, an `<a>` is rendered instead of a `<button>`.
     *
     * @param string $icon Icon name or raw SVG.
     * @param string $label Accessible label.
     * @param array<string, mixed> $options Options, see render(). `size` defaults to `icon`.
     * @return string
     */
    public function icon(string $icon, string $label, array $options = []): string
    {
        $options += ['size' => 'icon', 'aria-label' => $label, 'icon' => $icon];
        if (array_key_exists('url', $options)) {
            $url = $options['url'];
            unset($options['url']);

            return $this->link('', $url, $options);
        }

        return $this->render('', $options);
    }

    /**
     * Builds the inner content (icon + text).
     *
     * @param string $text Text.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function content(string $text, array $options): string
    {
        $text = $this->formatTemplate('text', ['text' => $this->text($text, $options['escape'] ?? true)]);
        $icon = '';
        if (!empty($options['icon'])) {
            $iconOptions = (array)($options['iconOptions'] ?? []);
            $iconOptions['class'] = $this->mergeClasses($this->getConfig('classes.icon'), $iconOptions['class'] ?? null);
            $icon = $this->Icon->render((string)$options['icon'], array_filter($iconOptions));
        }

        return ($options['iconPosition'] ?? 'start') === 'end' ? $text . $icon : $icon . $text;
    }
}
