<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Alert (`.alert`).
 *
 * ```
 * echo $this->Alert->render('Account updated', 'Your profile has been saved.', ['icon' => 'circle-check']);
 * echo $this->Alert->render('Something went wrong', null, ['variant' => 'destructive']);
 * echo $this->Alert->success('Saved!');
 * ```
 *
 * Variants: default, destructive.
 *
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class AlertHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * `types` maps the shortcut methods (success(), error(), warning(), info())
     * and flash element names to alert options.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'variant' => null,
        'class' => null,
        'attributes' => ['role' => 'alert'],
        'titleTag' => 'h2',
        'classes' => [
            'title' => null,
            'description' => null,
            'footer' => null,
        ],
        'types' => [
            'default' => ['icon' => 'info'],
            'success' => ['icon' => 'circle-check'],
            'error' => ['icon' => 'circle-alert', 'variant' => 'destructive'],
            'warning' => ['icon' => 'triangle-alert'],
            'info' => ['icon' => 'info'],
        ],
        'templates' => [
            'alert' => '<div{{attrs}}>{{icon}}{{title}}{{description}}{{footer}}</div>',
            'title' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
            'description' => '<section{{attrs}}>{{content}}</section>',
            'footer' => '<footer{{attrs}}>{{content}}</footer>',
        ],
    ];

    /**
     * Renders an alert.
     *
     * ### Options
     *
     * - `variant`: `destructive` or null.
     * - `icon`: Icon name or raw SVG (false/null for none).
     * - `footer`: HTML for the alert footer (actions).
     * - `titleTag`: Tag of the title (default `h2`).
     * - `titleOptions`, `descriptionOptions`, `footerOptions`: Attributes of the parts.
     * - `escape`: Escape title and description (default true). The description can be
     *   an array of strings, rendered as `<ul>`.
     *
     * @param string|null $title Title.
     * @param array<string>|string|null $description Description.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(?string $title, array|string|null $description = null, array $options = []): string
    {
        $escape = $options['escape'] ?? true;

        $titleHtml = '';
        if ($title !== null && $title !== '') {
            $titleHtml = $this->renderTemplate(
                'title',
                $this->partAttributes('title', (array)($options['titleOptions'] ?? [])),
                ['tag' => $options['titleTag'] ?? $this->getConfig('titleTag'), 'content' => $this->text($title, $escape)],
            );
        }

        $descriptionHtml = '';
        if ($description !== null && $description !== '' && $description !== []) {
            if (is_array($description)) {
                $items = array_map(fn($item) => '<li>' . $this->text((string)$item, $escape) . '</li>', $description);
                $content = '<ul>' . implode('', $items) . '</ul>';
            } else {
                $content = $this->text($description, $escape);
            }
            $descriptionHtml = $this->renderTemplate(
                'description',
                $this->partAttributes('description', (array)($options['descriptionOptions'] ?? [])),
                ['content' => $content],
            );
        }

        $footerHtml = '';
        if (!empty($options['footer'])) {
            $footerHtml = $this->renderTemplate(
                'footer',
                $this->partAttributes('footer', (array)($options['footerOptions'] ?? [])),
                ['content' => $options['footer']],
            );
        }

        $icon = $options['icon'] ?? null;
        $attributes = $this->rootAttributes('alert', $options, [
            'icon', 'footer', 'titleTag', 'titleOptions', 'descriptionOptions', 'footerOptions',
        ]);

        return $this->renderTemplate('alert', $attributes, [
            'icon' => $icon ? $this->Icon->render((string)$icon) : '',
            'title' => $titleHtml,
            'description' => $descriptionHtml,
            'footer' => $footerHtml,
        ], $options['templateVars'] ?? []);
    }

    /**
     * Renders an alert using a configured type (see config `types`).
     *
     * @param string $type Type name, e.g. `success`.
     * @param string|null $title Title.
     * @param array<string>|string|null $description Description.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function type(string $type, ?string $title, array|string|null $description = null, array $options = []): string
    {
        $typeOptions = (array)($this->getConfig('types.' . $type) ?? $this->getConfig('types.default') ?? []);
        $options += $typeOptions;
        $options['data-type'] ??= $type;

        return $this->render($title, $description, $options);
    }

    /**
     * Success alert.
     *
     * @param string|null $title Title.
     * @param array<string>|string|null $description Description.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function success(?string $title, array|string|null $description = null, array $options = []): string
    {
        return $this->type('success', $title, $description, $options);
    }

    /**
     * Error alert (destructive variant).
     *
     * @param string|null $title Title.
     * @param array<string>|string|null $description Description.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function error(?string $title, array|string|null $description = null, array $options = []): string
    {
        return $this->type('error', $title, $description, $options);
    }

    /**
     * Warning alert.
     *
     * @param string|null $title Title.
     * @param array<string>|string|null $description Description.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function warning(?string $title, array|string|null $description = null, array $options = []): string
    {
        return $this->type('warning', $title, $description, $options);
    }

    /**
     * Info alert.
     *
     * @param string|null $title Title.
     * @param array<string>|string|null $description Description.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function info(?string $title, array|string|null $description = null, array $options = []): string
    {
        return $this->type('info', $title, $description, $options);
    }
}
