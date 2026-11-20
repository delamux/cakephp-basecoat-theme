<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use Cake\Routing\Router;
use Throwable;
use function Cake\I18n\__d;

/**
 * Basecoat Sidebar (`aside.sidebar`). Requires Basecoat JS.
 *
 * ```
 * echo $this->Sidebar->render([
 *     ['label' => 'Dashboard', 'url' => '/', 'icon' => 'layout-dashboard'],
 *     ['type' => 'group', 'label' => 'Documents', 'items' => [
 *         ['label' => 'Reports', 'url' => ['controller' => 'Reports', 'action' => 'index'], 'icon' => 'file-chart'],
 *     ]],
 *     ['type' => 'submenu', 'label' => 'Settings', 'icon' => 'settings-2', 'items' => [...]],
 * ], [
 *     'header' => $this->Html->link('Fernway', '/'),
 *     'footer' => $this->DropdownMenu->render(...),
 * ]);
 *
 * echo $this->Sidebar->toggle(); // button that opens/closes it
 * ```
 *
 * Items whose URL matches the current request get `aria-current="page"`.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 * @property \BasecoatTheme\View\Helper\ButtonHelper $Button
 */
class SidebarHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Icon', 'BasecoatTheme.Button'];

    /**
     * Default configuration.
     *
     * - `id`: Default DOM id (used by toggle()).
     * - `currentMatch`: `exact` or `prefix` (an item is current when the request
     *   path starts with its URL; `/` only matches exactly).
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'id' => 'sidebar',
        'label' => 'Sidebar navigation',
        'side' => 'left',
        'open' => true,
        'currentMatch' => 'exact',
        'class' => null,
        'attributes' => [],
        'classes' => [
            'nav' => null,
            'header' => null,
            'content' => 'scrollbar-sm',
            'footer' => null,
            'group' => null,
            'groupLabel' => null,
            'item' => null,
        ],
        'templates' => [
            'sidebar' => '<aside{{attrs}}><nav{{navAttrs}}>{{header}}{{content}}{{footer}}</nav></aside>',
            'header' => '<header{{attrs}}>{{content}}</header>',
            'content' => '<section{{attrs}}>{{content}}</section>',
            'footer' => '<footer{{attrs}}>{{content}}</footer>',
            'group' => '<div{{attrs}}>{{label}}<ul>{{content}}</ul></div>',
            'groupLabel' => '<h3{{attrs}}>{{content}}</h3>',
            'list' => '<ul>{{content}}</ul>',
            'item' => '<li><a href="{{url}}"{{attrs}}>{{icon}}<span>{{content}}</span>{{badge}}</a></li>',
            'submenu' => '<li><details{{attrs}}><summary aria-controls="{{contentId}}">{{icon}}<span>{{content}}</span></summary><ul id="{{contentId}}">{{items}}</ul></details></li>',
            'separator' => '<hr role="separator">',
        ],
    ];

    /**
     * Renders the sidebar.
     *
     * Menu items: `item` (default: `label`, `url`, `icon`, `current`, `badge`, `attributes`),
     * `group` (`label`, `items`, `attributes`), `submenu` (`label`, `icon`, `open`, `items`)
     * and `separator` (or `-`). Top-level items not wrapped in a group are grouped automatically.
     *
     * ### Options
     *
     * - `id`, `label` (nav aria-label), `side` (`left`/`right`), `open`.
     * - `header`, `footer`: HTML.
     * - `content`: Raw HTML for the `<section>` (replaces `$menu`).
     * - `navOptions`, `headerOptions`, `contentOptions`, `footerOptions`.
     * - `escape`: Escape labels (default true).
     * - `class` and any attribute for the `<aside>`.
     *
     * @param array<array<string, mixed>|string> $menu Menu.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $menu, array $options = []): string
    {
        $id = (string)($options['id'] ?? $this->getConfig('id'));
        $escape = $options['escape'] ?? true;
        $open = (bool)($options['open'] ?? $this->getConfig('open'));

        $header = '';
        if (!empty($options['header'])) {
            $header = $this->renderTemplate(
                'header',
                $this->partAttributes('header', (array)($options['headerOptions'] ?? [])),
                ['content' => $options['header']],
            );
        }
        $footer = '';
        if (!empty($options['footer'])) {
            $footer = $this->renderTemplate(
                'footer',
                $this->partAttributes('footer', (array)($options['footerOptions'] ?? [])),
                ['content' => $options['footer']],
            );
        }
        $content = $this->renderTemplate(
            'content',
            $this->partAttributes('content', (array)($options['contentOptions'] ?? [])),
            ['content' => $options['content'] ?? $this->menu($menu, $id . '-content', $escape)],
        );

        $navAttributes = (array)($options['navOptions'] ?? []) + [
            'aria-label' => $options['label'] ?? $this->getConfig('label'),
        ];

        $options += [
            'id' => $id,
            'data-side' => $options['side'] ?? $this->getConfig('side'),
            'aria-hidden' => $this->bool(!$open),
            'inert' => !$open,
        ];

        $attributes = $this->rootAttributes('sidebar', $options, [
            'label', 'side', 'open', 'header', 'footer', 'content',
            'navOptions', 'headerOptions', 'contentOptions', 'footerOptions',
        ]);

        return $this->formatTemplate('sidebar', [
            'attrs' => $this->formatAttributes($attributes),
            'navAttrs' => $this->formatAttributes($this->partAttributes('nav', $navAttributes)),
            'header' => $header,
            'content' => $content,
            'footer' => $footer,
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }

    /**
     * Renders the menu (groups, items, submenus, separators).
     *
     * @param array<array<string, mixed>|string> $items Items.
     * @param string $prefix Id prefix.
     * @param bool $escape Escape labels.
     * @return string
     */
    public function menu(array $items, string $prefix = 'content', bool $escape = true): string
    {
        $out = '';
        $loose = [];
        $index = 0;
        $looseStart = 0;
        $flush = function () use (&$loose, &$out, $prefix, $escape, &$looseStart): void {
            if ($loose) {
                $out .= $this->group(['items' => $loose], $prefix . '-' . $looseStart, $escape);
                $loose = [];
            }
        };

        foreach ($items as $item) {
            $index++;
            if ($item === '-') {
                $item = ['type' => 'separator'];
            }
            $type = $item['type'] ?? 'item';
            if ($type === 'group') {
                $flush();
                $out .= $this->group($item, $prefix . '-' . $index, $escape);
            } elseif ($type === 'separator') {
                $flush();
                $out .= $this->formatTemplate('separator', []);
            } else {
                if (!$loose) {
                    $looseStart = $index;
                }
                $loose[] = $item;
            }
        }
        $flush();

        return $out;
    }

    /**
     * Renders a toggle button for the sidebar.
     *
     * @param array<string, mixed> $options Button options plus `target` (sidebar id) and `label`.
     * @return string
     */
    public function toggle(array $options = []): string
    {
        $target = (string)($options['target'] ?? $this->getConfig('id'));
        $label = (string)($options['label'] ?? __d('basecoat_theme', 'Toggle sidebar'));
        unset($options['target'], $options['label']);
        $options += [
            'variant' => 'ghost',
            'size' => 'icon-sm',
            // Handled by webroot/js/basecoat-theme.js (no inline JS: CSP without 'unsafe-inline')
            'data-sidebar-toggle' => $target,
            'aria-controls' => $target,
        ];

        return $this->Button->icon($options['icon'] ?? 'panel-left', $label, $options);
    }

    /**
     * Renders a group of items.
     *
     * @param array<string, mixed> $group Group definition.
     * @param string $prefix Id prefix.
     * @param bool $escape Escape labels.
     * @return string
     */
    protected function group(array $group, string $prefix, bool $escape): string
    {
        $labelId = $group['id'] ?? ($prefix . '-label');
        $label = '';
        if (!empty($group['label'])) {
            $label = $this->renderTemplate(
                'groupLabel',
                $this->partAttributes('groupLabel', ['id' => $labelId]),
                ['content' => $this->text((string)$group['label'], (bool)($group['escape'] ?? $escape))],
            );
        }
        $attributes = (array)($group['attributes'] ?? []) + [
            'role' => 'group',
            'aria-labelledby' => $label !== '' ? $labelId : null,
        ];

        return $this->renderTemplate('group', $this->partAttributes('group', $attributes), [
            'label' => $label,
            'content' => $this->items((array)($group['items'] ?? []), $prefix, $escape),
        ]);
    }

    /**
     * Renders `<li>` items.
     *
     * @param array<array<string, mixed>|string> $items Items.
     * @param string $prefix Id prefix.
     * @param bool $escape Escape labels.
     * @return string
     */
    protected function items(array $items, string $prefix, bool $escape): string
    {
        $out = '';
        $index = 0;
        foreach ($items as $item) {
            $index++;
            if ($item === '-') {
                $out .= $this->formatTemplate('separator', []);
                continue;
            }
            $type = $item['type'] ?? 'item';
            $itemEscape = (bool)($item['escape'] ?? $escape);
            $label = $this->text((string)($item['label'] ?? ''), $itemEscape);
            $icon = $this->Icon->optional($item['icon'] ?? null);

            if ($type === 'separator') {
                $out .= $this->formatTemplate('separator', []);
            } elseif ($type === 'submenu') {
                $submenuId = $item['id'] ?? ('submenu-' . $prefix . '-' . $index);
                $children = (array)($item['items'] ?? []);
                $open = $item['open'] ?? $this->containsCurrent($children);
                $attributes = (array)($item['attributes'] ?? []) + ['id' => $submenuId, 'open' => (bool)$open];
                $out .= $this->renderTemplate('submenu', $this->cleanAttributes($attributes), [
                    'contentId' => $submenuId . '-content',
                    'icon' => $icon,
                    'content' => $label,
                    'items' => $this->items($children, $prefix . '-' . $index, $escape),
                ]);
            } else {
                $url = $item['url'] ?? '#';
                $attributes = (array)($item['attributes'] ?? []) + [
                    'aria-current' => $this->isCurrent($item) ? 'page' : null,
                    'class' => $item['class'] ?? null,
                ];
                $out .= $this->renderTemplate('item', $this->partAttributes('item', $attributes), [
                    'url' => $url === '#' ? '#' : $this->Url->build($url),
                    'icon' => $icon,
                    'content' => $label,
                    'badge' => $item['badge'] ?? '',
                ]);
            }
        }

        return $out;
    }

    /**
     * Whether an item points to the current request.
     *
     * @param array<string, mixed> $item Item.
     * @return bool
     */
    protected function isCurrent(array $item): bool
    {
        if (array_key_exists('current', $item)) {
            return (bool)$item['current'];
        }
        if (!isset($item['url']) || $item['url'] === '#') {
            return false;
        }

        $request = $this->getView()->getRequest();
        try {
            $here = $this->path(Router::reverse($request));
        } catch (Throwable) {
            $here = $this->path($request->getRequestTarget());
        }
        $target = $this->path($this->Url->build($item['url'], ['escape' => false]));
        $mode = $item['currentMatch'] ?? $this->getConfig('currentMatch');

        if ($mode === 'prefix' && $target !== '/' && $target !== '') {
            return $here === $target || str_starts_with($here, rtrim($target, '/') . '/');
        }

        return $here === $target;
    }

    /**
     * Whether any (nested) item is current.
     *
     * @param array<array<string, mixed>|string> $items Items.
     * @return bool
     */
    protected function containsCurrent(array $items): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['type'] ?? 'item') === 'item' && $this->isCurrent($item)) {
                return true;
            }
            if (!empty($item['items']) && $this->containsCurrent((array)$item['items'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Path of a URL without query string/fragment and trailing slash.
     *
     * @param string $url URL.
     * @return string
     */
    protected function path(string $url): string
    {
        $path = (string)parse_url($url, PHP_URL_PATH);

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
