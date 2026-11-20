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
 * // Larger touch target (44px) for an icon-only control
 * echo $this->Button->icon('bell', 'Notifications', ['variant' => 'ghost', 'class' => 'size-11']);
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
            'indicatorDot' => 'absolute top-1 right-1 size-2 rounded-full bg-destructive',
            'indicatorCount' => 'absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-none font-medium text-white',
        ],
        // `collapse` option: classes of the label per breakpoint (literal strings so a
        // compiled Tailwind build can see them).
        'collapseClasses' => [
            'sm' => 'sr-only sm:not-sr-only',
            'md' => 'sr-only md:not-sr-only',
            'lg' => 'sr-only lg:not-sr-only',
            'xl' => 'sr-only xl:not-sr-only',
        ],
        'templates' => [
            'button' => '<button{{attrs}}>{{content}}</button>',
            'link' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
            'text' => '{{text}}',
            // Accessible name of icon-only buttons (icon()).
            'iconLabel' => '<span class="sr-only">{{label}}</span>',
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
     * - `collapse`: `sm`, `md`, `lg`, `xl` (or `true` = `sm`): with an icon, the label is only
     *   visible from that breakpoint (icon-only below it, label kept for screen readers and
     *   as `title`). Handy for header actions on mobile.
     * - `indicator`: `true` adds a notification dot; an int/string adds a count badge. The
     *   button gets the `relative` class. `false`, `null`, `''`, `0` and `'0'` render no indicator
     *   at all (no badge, no screen reader text, no `relative` class), so a count can be passed as is.
     * - `indicatorLabel`: Screen reader text of the indicator (default "New" / "{0} new", pluralized);
     *   `''` for none (when the button text already says it).
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
        $options = $this->collapseOptions($text, $options);
        $content = $this->content($text, $options) . $this->indicator($options);
        $options = $this->indicatorClass($options);
        $attributes = $this->rootAttributes(
            'btn',
            $options,
            ['icon', 'iconPosition', 'iconOptions', 'collapse', 'indicator', 'indicatorLabel'],
        );

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
        $options = $this->collapseOptions($text, $options);
        $content = $this->content($text, $options) . $this->indicator($options);
        $options = $this->indicatorClass($options);
        $href = $this->Url->build($url, ['fullBase' => (bool)($options['fullBase'] ?? false)]);
        $attributes = $this->rootAttributes(
            'btn',
            $options,
            ['icon', 'iconPosition', 'iconOptions', 'fullBase', 'href', 'collapse', 'indicator', 'indicatorLabel'],
        );

        return $this->renderTemplate(
            'link',
            $attributes,
            ['url' => $href, 'content' => $content],
            $options['templateVars'] ?? [],
        );
    }

    /**
     * Renders an icon-only button.
     *
     * The accessible name is `$label`, rendered inside the control as visually hidden text
     * (`<span class="sr-only">`), as recommended by the Lucide accessibility guide; the icon
     * stays `aria-hidden`. With an `indicator`, its text joins the name ("Notifications, 3 new").
     * An explicit `aria-label` option is still honoured (it then takes precedence over the text).
     *
     * When `url` is given in `$options`, an `<a>` is rendered instead of a `<button>`.
     *
     * @param string $icon Icon name or raw SVG.
     * @param string $label Accessible label (plain text, always escaped).
     * @param array<string, mixed> $options Options, see render(). `size` defaults to `icon`.
     * @return string
     */
    public function icon(string $icon, string $label, array $options = []): string
    {
        if ($this->hasIndicator($options)) {
            // One name for the control: the indicator text goes into the same hidden span.
            $label .= ', ' . $this->indicatorText($options);
            $options['indicatorLabel'] = '';
        }
        $text = $this->formatTemplate('iconLabel', ['label' => h($label)]);
        // The label is already escaped into the hidden span: `escape` is always off.
        $options = ['escape' => false] + $options + ['size' => 'icon', 'icon' => $icon];
        if (array_key_exists('url', $options)) {
            $url = $options['url'];
            unset($options['url']);

            return $this->link($text, $url, $options);
        }

        return $this->render($text, $options);
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
        if (!empty($options['collapse']) && $text !== '') {
            $text = sprintf('<span class="%s">%s</span>', h((string)$options['collapse']), $text);
        }
        $icon = '';
        if (!empty($options['icon'])) {
            $iconOptions = (array)($options['iconOptions'] ?? []);
            $iconOptions['class'] = $this->mergeClasses($this->getConfig('classes.icon'), $iconOptions['class'] ?? null);
            $icon = $this->Icon->render((string)$options['icon'], array_filter($iconOptions));
        }

        return ($options['iconPosition'] ?? 'start') === 'end' ? $text . $icon : $icon . $text;
    }

    /**
     * Resolves the `collapse` option into the label classes and adds a `title`.
     *
     * Without an icon the option is dropped: an icon-only button needs an icon.
     *
     * @param string $text Button text.
     * @param array<string, mixed> $options Options.
     * @return array<string, mixed>
     */
    protected function collapseOptions(string $text, array $options): array
    {
        $collapse = $options['collapse'] ?? null;
        unset($options['collapse']);
        if (!$collapse || empty($options['icon']) || $text === '') {
            return $options;
        }

        $breakpoint = $collapse === true ? 'sm' : (string)$collapse;
        $classes = (array)$this->getConfig('collapseClasses');
        $options['collapse'] = $classes[$breakpoint] ?? $classes['sm'];
        $options['title'] ??= $options['escape'] ?? true ? $text : strip_tags($text);

        return $options;
    }

    /**
     * Adds the `relative` class when an indicator is requested.
     *
     * @param array<string, mixed> $options Options.
     * @return array<string, mixed>
     */
    protected function indicatorClass(array $options): array
    {
        if ($this->hasIndicator($options)) {
            $options['class'] = $this->mergeClasses('relative', $options['class'] ?? null);
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $options Options.
     * @return bool
     */
    protected function hasIndicator(array $options): bool
    {
        $indicator = $options['indicator'] ?? false;

        return !in_array($indicator, [false, null, '', 0, '0'], true);
    }

    /**
     * Builds the indicator (dot or count badge) plus its screen reader label.
     *
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function indicator(array $options): string
    {
        if (!$this->hasIndicator($options)) {
            return '';
        }
        $indicator = $options['indicator'];
        $label = $this->indicatorText($options);
        $srText = $label !== '' ? '<span class="sr-only">' . h($label) . '</span>' : '';
        if ($indicator === true) {
            $visual = sprintf('<span class="%s" aria-hidden="true"></span>', h((string)$this->getConfig('classes.indicatorDot')));
        } else {
            $count = (string)$indicator;
            $visual = sprintf(
                '<span class="%s" aria-hidden="true">%s</span>',
                h((string)$this->getConfig('classes.indicatorCount')),
                h($count),
            );
        }

        return $visual . $srText;
    }

    /**
     * Screen reader text of the indicator.
     *
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function indicatorText(array $options): string
    {
        if (isset($options['indicatorLabel'])) {
            return (string)$options['indicatorLabel'];
        }

        $indicator = $options['indicator'];
        if ($indicator === true) {
            return __d('basecoat_theme', 'New');
        }
        // Non-numeric counts such as "9+" use the plural form.
        $count = is_numeric($indicator) ? (int)$indicator : 2;

        return __dn('basecoat_theme', '{0} new', '{0} new', $count, (string)$indicator);
    }
}
