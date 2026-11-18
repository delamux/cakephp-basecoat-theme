<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Tabs (`.tabs`). Requires Basecoat JS (`tabs.js` or `all.js`).
 *
 * ```
 * echo $this->Tabs->render([
 *     'Account' => $this->element('account'),
 *     'Password' => $this->element('password'),
 * ], ['id' => 'settings']);
 *
 * echo $this->Tabs->render([
 *     ['tab' => 'Outline', 'panel' => $table],
 *     ['tab' => 'Past Performance ' . $this->Badge->render('3'), 'panel' => '...', 'escape' => false],
 * ], ['active' => 2, 'variant' => 'line']);
 * ```
 */
class TabsHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'variant' => null,
        'orientation' => 'horizontal',
        'classes' => [
            'list' => null,
            'tab' => null,
            'panel' => null,
        ],
        'templates' => [
            'tabs' => '<div{{attrs}}>{{list}}{{panels}}</div>',
            'list' => '<nav{{attrs}}>{{content}}</nav>',
            'tab' => '<button{{attrs}}>{{content}}</button>',
            'panel' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Renders tabs.
     *
     * `$tabs` is either `label => panelHtml` or a list of arrays with keys:
     * `tab` (label), `panel` (HTML, optional), `id`, `escape`, `disabled`,
     * `tabOptions`, `panelOptions`.
     *
     * ### Options
     *
     * - `id`: Component id (generated when omitted). Tab/panel ids derive from it.
     * - `active`: 1-based index or `id` of the active tab (default 1).
     * - `orientation`: `horizontal` (default) or `vertical`.
     * - `variant`: Tab list variant, e.g. `line` (set on the `<nav>`).
     * - `listOptions`: Attributes of the `<nav role="tablist">`.
     * - `escape`: Escape tab labels (default true). Panels are never escaped.
     * - `class` and any attribute for the root.
     *
     * @param array<string|int, mixed> $tabs Tabs.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $tabs, array $options = []): string
    {
        $id = (string)($options['id'] ?? $this->uniqueId('tabs'));
        $active = $options['active'] ?? 1;
        $escape = $options['escape'] ?? true;
        $orientation = $options['orientation'] ?? $this->getConfig('orientation');
        $variant = array_key_exists('variant', $options) ? $options['variant'] : $this->getConfig('variant');

        $buttons = '';
        $panels = '';
        $index = 0;
        foreach ($tabs as $key => $tab) {
            $index++;
            if (!is_array($tab)) {
                $tab = ['tab' => (string)$key, 'panel' => $tab];
            }
            $tabId = $tab['id'] ?? $index;
            $isActive = $active === $index || (is_string($active) && $active === (string)$tabId);
            $disabled = !empty($tab['disabled']);
            $tabDomId = $id . '-tab-' . $tabId;
            $panelDomId = $id . '-panel-' . $tabId;
            $hasPanel = isset($tab['panel']) && $tab['panel'] !== '';

            $tabAttributes = (array)($tab['tabOptions'] ?? []) + [
                'type' => 'button',
                'role' => 'tab',
                'id' => $tabDomId,
                'aria-controls' => $hasPanel ? $panelDomId : null,
                'aria-selected' => $this->bool($isActive),
                'tabindex' => $isActive && !$disabled ? '0' : '-1',
                'disabled' => $disabled,
            ];
            $buttons .= $this->renderTemplate('tab', $this->partAttributes('tab', $tabAttributes), [
                'content' => $this->text((string)($tab['tab'] ?? ''), (bool)($tab['escape'] ?? $escape)),
            ]);

            if ($hasPanel) {
                $panelAttributes = (array)($tab['panelOptions'] ?? []) + [
                    'role' => 'tabpanel',
                    'id' => $panelDomId,
                    'aria-labelledby' => $tabDomId,
                    'tabindex' => '-1',
                    'aria-selected' => $this->bool($isActive),
                    'hidden' => !$isActive,
                ];
                $panels .= $this->renderTemplate('panel', $this->partAttributes('panel', $panelAttributes), [
                    'content' => $tab['panel'],
                ]);
            }
        }

        $listAttributes = (array)($options['listOptions'] ?? []) + [
            'role' => 'tablist',
            'aria-orientation' => $orientation,
            'data-variant' => $variant,
        ];
        $list = $this->renderTemplate('list', $this->partAttributes('list', $listAttributes), ['content' => $buttons]);

        $options['id'] = $id;
        $options['variant'] = null;
        $attributes = $this->rootAttributes('tabs', $options, ['active', 'orientation', 'listOptions']);

        return $this->renderTemplate('tabs', $attributes, [
            'list' => $list,
            'panels' => $panels,
        ], $options['templateVars'] ?? []);
    }
}
