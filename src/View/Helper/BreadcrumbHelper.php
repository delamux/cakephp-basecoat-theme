<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\I18n\__d;

/**
 * Basecoat Breadcrumb (`nav.breadcrumb`).
 *
 * ```
 * echo $this->Breadcrumb->render([
 *     'Home' => '/',
 *     'Wines' => ['controller' => 'Wines', 'action' => 'index'],
 *     'Rioja 2019',                                   // current page (no URL)
 * ]);
 *
 * // Collapse middle items
 * echo $this->Breadcrumb->render($crumbs, ['collapse' => 3]);
 * ```
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class BreadcrumbHelper extends AbstractComponentHelper
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
        'class' => null,
        'attributes' => [],
        'separator' => 'chevron-right',
        'classes' => [
            'list' => null,
            'item' => null,
        ],
        'templates' => [
            'breadcrumb' => '<nav{{attrs}}><ol{{listAttrs}}>{{content}}</ol></nav>',
            'item' => '<li{{attrs}}><a href="{{url}}">{{icon}}{{content}}</a></li>',
            'current' => '<li{{attrs}}><span aria-current="page">{{icon}}{{content}}</span></li>',
            'separator' => '<li aria-hidden="true">{{content}}</li>',
            'ellipsis' => '<li><span aria-hidden="true">{{content}}</span></li>',
        ],
    ];

    /**
     * Renders a breadcrumb.
     *
     * Crumbs: `label => url`, a bare `label` (current page, no link), or arrays with
     * `label`, `url`, `icon`, `attributes`. The last crumb is always the current page.
     *
     * ### Options
     *
     * - `separator`: Separator icon name/raw HTML (default `chevron-right`).
     * - `collapse`: When there are more crumbs than this number, the middle ones are
     *   replaced by an ellipsis (first and last two are kept).
     * - `listOptions`: Attributes of the `<ol>`.
     * - `escape`: Escape labels (default true).
     * - `class` and any attribute for the `<nav>`.
     *
     * @param array<string|int, mixed> $crumbs Crumbs.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $crumbs, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $items = [];
        foreach ($crumbs as $key => $crumb) {
            if (is_array($crumb) && isset($crumb['label'])) {
                $items[] = $crumb;
            } elseif (is_int($key)) {
                $items[] = ['label' => (string)$crumb];
            } else {
                $items[] = ['label' => (string)$key, 'url' => $crumb];
            }
        }

        $collapse = $options['collapse'] ?? null;
        $collapsed = false;
        if ($collapse !== null && count($items) > (int)$collapse && count($items) > 3) {
            $items = array_merge([$items[0]], [['ellipsis' => true]], array_slice($items, -2));
            $collapsed = true;
        }

        $separatorOption = $options['separator'] ?? $this->getConfig('separator');
        $separator = $this->formatTemplate('separator', [
            'content' => $this->Icon->render((string)$separatorOption, ['data-rtl-flip' => 'true']),
        ]);

        $parts = [];
        $last = count($items) - 1;
        foreach ($items as $i => $item) {
            if (!empty($item['ellipsis'])) {
                $parts[] = $this->formatTemplate('ellipsis', ['content' => $this->Icon->render('ellipsis')]);
                continue;
            }
            $data = [
                'icon' => $this->Icon->optional($item['icon'] ?? null),
                'content' => $this->text((string)$item['label'], (bool)($item['escape'] ?? $escape)),
            ];
            $attrs = $this->formatAttributes($this->partAttributes('item', (array)($item['attributes'] ?? [])));
            if ($i === $last || !isset($item['url'])) {
                $parts[] = $this->formatTemplate('current', $data + ['attrs' => $attrs]);
            } else {
                $parts[] = $this->formatTemplate('item', $data + [
                    'attrs' => $attrs,
                    'url' => $this->Url->build($item['url']),
                ]);
            }
        }

        $options += ['aria-label' => __d('basecoat_theme', 'Breadcrumb')];
        if ($collapsed) {
            $options['data-collapsed'] = 'true';
        }
        $attributes = $this->rootAttributes('breadcrumb', $options, ['separator', 'collapse', 'listOptions']);

        return $this->formatTemplate('breadcrumb', [
            'attrs' => $this->formatAttributes($attributes),
            'listAttrs' => $this->formatAttributes($this->partAttributes('list', (array)($options['listOptions'] ?? []))),
            'content' => implode($separator, $parts),
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }
}
