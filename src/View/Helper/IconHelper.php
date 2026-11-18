<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use InvalidArgumentException;
use function Cake\Core\h;

/**
 * Inline SVG icons (Lucide, ISC license) used by the Basecoat components.
 *
 * ```
 * echo $this->Icon->render('circle-check');
 * echo $this->Icon->render('loader', ['class' => 'size-3.5 animate-spin']);
 *
 * // Register your own icons (inner SVG markup, 24x24 viewBox)
 * $this->addHelper('BasecoatTheme.Icon', ['icons' => ['wine' => '<path d="..."/>']]);
 * $this->Icon->add(['grape' => '<circle cx="12" cy="12" r="3"/>']);
 * ```
 *
 * Any string starting with `<` is considered raw SVG/HTML and returned untouched, so
 * every component accepting an `icon` option also accepts your own markup.
 */
class IconHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => 'size-4',
        'attributes' => [
            'viewBox' => '0 0 24 24',
            'fill' => 'none',
            'stroke' => 'currentColor',
            'stroke-width' => '2',
            'stroke-linecap' => 'round',
            'stroke-linejoin' => 'round',
            'aria-hidden' => 'true',
        ],
        'icons' => [],
        'templates' => [
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg"{{attrs}}>{{content}}</svg>',
        ],
    ];

    /**
     * Built-in icon set (inner SVG markup).
     *
     * @var array<string, string>
     */
    protected array $icons = [
        'arrow-down' => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
        'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-up' => '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
        'arrow-up-right' => '<path d="M7 7h10v10"/><path d="M7 17 17 7"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'chart-column' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-up' => '<path d="m18 15-6-6-6 6"/>',
        'chevrons-left' => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
        'chevrons-right' => '<path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>',
        'chevrons-up-down' => '<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>',
        'circle' => '<circle cx="12" cy="12" r="10"/>',
        'circle-alert' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
        'circle-check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'circle-help' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'circle-plus' => '<circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/>',
        'circle-user' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>',
        'circle-x' => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'columns-2' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/>',
        'copy' => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'credit-card' => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>',
        'ellipsis' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        'ellipsis-vertical' => '<circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>',
        'file-chart' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 18v-1"/><path d="M12 18v-6"/><path d="M16 18v-3"/>',
        'file-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'folder' => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
        'grip-vertical' => '<circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>',
        'house' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'layout-dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'list' => '<path d="M3 12h.01"/><path d="M3 18h.01"/><path d="M3 6h.01"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M8 6h13"/>',
        'loader' => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'menu' => '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>',
        'minus' => '<path d="M5 12h14"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'panel-left' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>',
        'pencil' => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'settings-2' => '<path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'trending-down' => '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',
        'trending-up' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'triangle-alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    ];

    /**
     * Renders an icon.
     *
     * ### Options
     *
     * - `class`: Extra classes (the config `class`, `size-4` by default, is kept unless
     *   `replaceClass` is true).
     * - `replaceClass`: Replace the default classes instead of appending.
     * - `title`: Accessible title. Removes `aria-hidden` and adds `role="img"`.
     * - Any other key is rendered as an SVG attribute.
     *
     * @param string $name Icon name, or raw SVG markup (returned as is).
     * @param array<string, mixed> $options Options.
     * @return string
     * @throws \InvalidArgumentException When the icon does not exist.
     */
    public function render(string $name, array $options = []): string
    {
        if (str_starts_with(ltrim($name), '<')) {
            return $name;
        }
        $content = $this->get($name);
        if ($content === null) {
            throw new InvalidArgumentException(sprintf('Unknown icon `%s`. Register it with IconHelper::add().', $name));
        }

        $title = $options['title'] ?? null;
        if (!empty($options['replaceClass'])) {
            $attributes = (array)$this->getConfig('attributes') + ['class' => null];
            $attributes['class'] = $this->mergeClasses($options['class'] ?? null);
            unset($options['class']);
            $attributes = $this->cleanAttributes($this->stripOptions($options, ['replaceClass', 'title']) + $attributes);
        } else {
            $attributes = $this->rootAttributes([], $options, ['replaceClass', 'title']);
        }

        if ($title !== null) {
            unset($attributes['aria-hidden']);
            $attributes['role'] = 'img';
            $content = '<title>' . h($title) . '</title>' . $content;
        }

        return $this->renderTemplate('icon', $attributes, ['content' => $content]);
    }

    /**
     * Renders an icon if `$icon` is not empty; returns an empty string otherwise.
     *
     * @param string|null $icon Icon name or raw markup.
     * @param array<string, mixed> $options Options passed to render().
     * @return string
     */
    public function optional(?string $icon, array $options = []): string
    {
        if ($icon === null || $icon === '') {
            return '';
        }

        return $this->render($icon, $options);
    }

    /**
     * Registers icons (name => inner SVG markup).
     *
     * @param array<string, string> $icons Icons.
     * @return $this
     */
    public function add(array $icons)
    {
        $this->setConfig('icons', $icons);

        return $this;
    }

    /**
     * Whether an icon is registered.
     *
     * @param string $name Icon name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * Returns the inner SVG markup of an icon.
     *
     * @param string $name Icon name.
     * @return string|null
     */
    public function get(string $name): ?string
    {
        $custom = (array)$this->getConfig('icons', []);

        return $custom[$name] ?? $this->icons[$name] ?? null;
    }

    /**
     * Returns the names of all available icons.
     *
     * @return array<string>
     */
    public function names(): array
    {
        $names = array_unique(array_merge(array_keys($this->icons), array_keys((array)$this->getConfig('icons', []))));
        sort($names);

        return $names;
    }
}
