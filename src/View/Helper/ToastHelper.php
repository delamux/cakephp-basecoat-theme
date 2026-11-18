<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Basecoat Toast (`.toaster` + `.toast`). Requires Basecoat JS (`toast.js` or `all.js`).
 *
 * ```
 * // Once in the layout
 * echo $this->Toast->toaster();
 *
 * // Server-rendered toasts
 * echo $this->Toast->toaster([
 *     ['title' => 'Saved', 'description' => 'Your changes were saved.'],
 *     ['category' => 'error', 'title' => 'Oops', 'duration' => -1],
 * ]);
 *
 * echo $this->Toast->render('Event created', ['category' => 'info', 'action' => ['label' => 'Undo', 'url' => '/undo']]);
 * ```
 *
 * Categories: success (default), error, info, warning.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class ToastHelper extends AbstractComponentHelper
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
        'id' => 'toaster',
        'align' => null,
        'category' => 'success',
        'duration' => null,
        'class' => null,
        'attributes' => [],
        'icons' => [
            'success' => 'circle-check',
            'error' => 'circle-x',
            'info' => 'info',
            'warning' => 'triangle-alert',
        ],
        'classes' => [
            'toaster' => null,
            'content' => null,
            'action' => null,
            'cancel' => null,
        ],
        'templates' => [
            'toaster' => '<div{{attrs}}>{{content}}</div>',
            'toast' => '<div{{attrs}}><div{{contentAttrs}}>{{icon}}<section>{{title}}{{description}}</section>{{footer}}</div></div>',
            'title' => '<h2>{{content}}</h2>',
            'description' => '<p>{{content}}</p>',
            'footer' => '<footer>{{content}}</footer>',
            'buttonLink' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
            'button' => '<button type="button"{{attrs}}>{{content}}</button>',
        ],
    ];

    /**
     * Renders the toaster container, optionally with initial toasts.
     *
     * ### Options
     *
     * - `id`: Container id (default config `id`, `toaster`).
     * - `align`: `start`, `center` or `end` (`data-align`).
     * - `content`: Extra raw HTML appended inside.
     * - `class` and any attribute.
     *
     * @param array<array<string, mixed>> $toasts Toasts: each one is the options of render() plus `title`.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function toaster(array $toasts = [], array $options = []): string
    {
        $content = '';
        foreach ($toasts as $toast) {
            $title = (string)($toast['title'] ?? '');
            unset($toast['title']);
            $content .= $this->render($title, $toast);
        }
        $content .= $options['content'] ?? '';

        $attributes = $options + [
            'id' => $this->getConfig('id'),
            'data-align' => $options['align'] ?? $this->getConfig('align'),
        ];
        $attributes['class'] = $this->mergeClasses('toaster', $this->getConfig('classes.toaster'), $options['class'] ?? null);
        $attributes = $this->cleanAttributes($this->stripOptions($attributes, ['align', 'content']));

        return $this->renderTemplate('toaster', $attributes, ['content' => $content]);
    }

    /**
     * Renders a single toast.
     *
     * ### Options
     *
     * - `category`: success, error, info, warning (`data-category`).
     * - `description`: Secondary text.
     * - `duration`: Milliseconds; `-1` keeps it open.
     * - `icon`: Icon name/raw SVG; `false` for none. Defaults to the category icon.
     * - `action`, `cancel`: `['label' => ..., 'url' => ...]` or `['label' => ..., 'onclick' => ...]`.
     * - `escape`: Escape title/description (default true).
     * - `class` and any attribute for the toast.
     *
     * @param string $title Title.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $title, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $category = (string)($options['category'] ?? $this->getConfig('category'));
        $duration = $options['duration'] ?? $this->getConfig('duration');

        $icon = $options['icon'] ?? $this->getConfig('icons.' . $category);
        $iconHtml = $icon ? $this->Icon->render((string)$icon, ['replaceClass' => true]) : '';

        $footer = '';
        if (!empty($options['action'])) {
            $footer .= $this->button((array)$options['action'], 'data-toast-action', 'action', null);
        }
        if (!empty($options['cancel'])) {
            $footer .= $this->button((array)$options['cancel'], 'data-toast-cancel', 'cancel', 'outline');
        }
        if ($footer !== '') {
            $footer = $this->formatTemplate('footer', ['content' => $footer]);
        }

        $rootOptions = $options + [
            'role' => $category === 'error' ? 'alert' : 'status',
            'aria-atomic' => 'true',
            'aria-hidden' => 'false',
            'data-category' => $category,
            'data-duration' => $duration !== null ? (string)$duration : null,
        ];
        $attributes = $this->rootAttributes('toast', $rootOptions, [
            'category', 'description', 'duration', 'icon', 'action', 'cancel', 'contentOptions',
        ]);

        return $this->formatTemplate('toast', [
            'attrs' => $this->formatAttributes($attributes),
            'contentAttrs' => $this->formatAttributes(
                $this->partAttributes('content', (array)($options['contentOptions'] ?? []), 'toast-content'),
            ),
            'icon' => $iconHtml,
            'title' => $title !== '' ? $this->formatTemplate('title', ['content' => $this->text($title, $escape)]) : '',
            'description' => !empty($options['description'])
                ? $this->formatTemplate('description', ['content' => $this->text((string)$options['description'], $escape)])
                : '',
            'footer' => $footer,
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }

    /**
     * Renders an action/cancel button.
     *
     * @param array<string, mixed> $button Button definition.
     * @param string $marker Data attribute marker.
     * @param string $part Part name.
     * @param string|null $variant Button variant.
     * @return string
     */
    protected function button(array $button, string $marker, string $part, ?string $variant): string
    {
        $attributes = $this->partAttributes($part, (array)($button['attributes'] ?? []) + [
            'data-variant' => $variant,
            'data-size' => 'sm',
            $marker => 'true',
            'onclick' => $button['onclick'] ?? null,
        ], 'btn');
        $label = h((string)($button['label'] ?? ''));
        $url = $button['url'] ?? $button['href'] ?? null;
        if ($url !== null) {
            unset($attributes['onclick']);

            return $this->renderTemplate('buttonLink', $attributes, ['url' => $this->Url->build($url), 'content' => $label]);
        }

        return $this->renderTemplate('button', $attributes, ['content' => $label]);
    }
}
