<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Basecoat Dropdown Menu (`.dropdown-menu`). Requires Basecoat JS.
 *
 * ```
 * echo $this->DropdownMenu->render('Actions', [
 *     ['label' => 'Edit', 'url' => ['action' => 'edit', 1], 'icon' => 'pencil'],
 *     ['label' => 'Make a copy', 'shortcut' => '⌘C'],
 *     '-',
 *     ['label' => 'Delete', 'variant' => 'destructive'],
 * ], ['trigger' => ['variant' => 'ghost', 'size' => 'icon-sm', 'icon' => 'ellipsis'], 'align' => 'end']);
 * ```
 *
 * Item types: `item` (default), `separator` (or the string `-`), `group`
 * (`label` + `items`), `heading`, `checkbox` (`checked`) and `radio` (`checked`).
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class DropdownMenuHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * - `trigger`: Default trigger options (it is a `.btn`): `variant`, `size`, `class`.
     * - `side` / `align`: Default popover position.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'trigger' => [
            'class' => 'btn',
            'variant' => 'outline',
            'size' => null,
        ],
        'side' => null,
        'align' => null,
        'classes' => [
            'popover' => null,
            'menu' => null,
            'item' => null,
            'heading' => null,
        ],
        'templates' => [
            'dropdown' => '<div{{attrs}}>{{trigger}}{{popover}}</div>',
            'trigger' => '<button{{attrs}}>{{content}}</button>',
            'popover' => '<div data-popover{{attrs}}><div{{menuAttrs}}>{{content}}</div></div>',
            'item' => '<div{{attrs}}>{{icon}}{{content}}{{shortcut}}</div>',
            'itemLink' => '<a href="{{url}}"{{attrs}}>{{icon}}{{content}}{{shortcut}}</a>',
            'shortcut' => '<span data-shortcut>{{content}}</span>',
            'separator' => '<hr role="separator">',
            'group' => '<div{{attrs}}>{{heading}}{{content}}</div>',
            'heading' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Renders a dropdown menu.
     *
     * ### Options
     *
     * - `id`: Component id (generated when omitted).
     * - `trigger`: Trigger button options: `variant`, `size`, `class`, `icon`,
     *   `iconPosition`, `label` and any attribute. Merged with config `trigger`. `label` is the
     *   accessible name of an icon-only trigger, rendered as visually hidden text inside the
     *   button (always escaped), e.g. `['icon' => 'ellipsis', 'label' => 'Actions']`.
     * - `side`: `top`, `bottom`, `left`, `right` (`data-side`).
     * - `align`: `start`, `center`, `end` (`data-align`).
     * - `popoverOptions`, `menuOptions`: Attributes of the parts.
     * - `escape`: Escape the trigger text and item labels (default true).
     * - `class` and any attribute for the root.
     *
     * @param string $trigger Trigger text (can be empty with an icon).
     * @param array<array<string, mixed>|string> $items Items.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $trigger, array $items, array $options = []): string
    {
        $id = (string)($options['id'] ?? $this->uniqueId('dropdown-menu'));
        $escape = $options['escape'] ?? true;

        $triggerOptions = (array)($options['trigger'] ?? []) + (array)$this->getConfig('trigger');
        $triggerContent = $this->text($trigger, $escape);
        if (!empty($triggerOptions['icon'])) {
            $icon = $this->Icon->render((string)$triggerOptions['icon']);
            $triggerContent = ($triggerOptions['iconPosition'] ?? 'start') === 'end'
                ? $triggerContent . $icon
                : $icon . $triggerContent;
        }
        if (($triggerOptions['label'] ?? '') !== '') {
            $triggerContent .= '<span class="sr-only">' . h((string)$triggerOptions['label']) . '</span>';
        }
        $triggerAttributes = [
            'type' => 'button',
            'id' => $id . '-trigger',
            'aria-haspopup' => 'menu',
            'aria-controls' => $id . '-menu',
            'aria-expanded' => 'false',
            'data-variant' => $triggerOptions['variant'] ?? null,
            'data-size' => $triggerOptions['size'] ?? null,
        ];
        $extra = $triggerOptions;
        unset($extra['variant'], $extra['size'], $extra['icon'], $extra['iconPosition'], $extra['label']);
        $triggerHtml = $this->renderTemplate(
            'trigger',
            $this->partAttributes('trigger', $extra + $triggerAttributes),
            ['content' => $triggerContent],
        );

        $popoverAttributes = (array)($options['popoverOptions'] ?? []) + [
            'id' => $id . '-popover',
            'aria-hidden' => 'true',
            'data-side' => $options['side'] ?? $this->getConfig('side'),
            'data-align' => $options['align'] ?? $this->getConfig('align'),
        ];
        $menuAttributes = (array)($options['menuOptions'] ?? []) + [
            'role' => 'menu',
            'id' => $id . '-menu',
            'aria-labelledby' => $id . '-trigger',
        ];
        $popover = $this->formatTemplate('popover', [
            'attrs' => $this->formatAttributes($this->partAttributes('popover', $popoverAttributes)),
            'menuAttrs' => $this->formatAttributes($this->partAttributes('menu', $menuAttributes)),
            'content' => $this->items($items, $id . '-items', $escape),
        ]);

        $options['id'] = $id;
        $attributes = $this->rootAttributes('dropdown-menu', $options, [
            'trigger', 'side', 'align', 'popoverOptions', 'menuOptions',
        ]);

        return $this->renderTemplate('dropdown', $attributes, [
            'trigger' => $triggerHtml,
            'popover' => $popover,
        ], $options['templateVars'] ?? []);
    }

    /**
     * Renders menu items (recursive for groups).
     *
     * @param array<array<string, mixed>|string> $items Items.
     * @param string $prefix Id prefix.
     * @param bool $escape Escape labels.
     * @return string
     */
    public function items(array $items, string $prefix = 'items', bool $escape = true): string
    {
        $out = '';
        $index = 0;
        foreach ($items as $item) {
            $index++;
            if ($item === '-' || $item === '|') {
                $item = ['type' => 'separator'];
            }
            if (is_string($item)) {
                $item = ['label' => $item];
            }
            $type = $item['type'] ?? 'item';
            $itemEscape = (bool)($item['escape'] ?? $escape);
            $attrs = (array)($item['attributes'] ?? []);

            if ($type === 'separator') {
                $out .= $this->formatTemplate('separator', []);
                continue;
            }
            if ($type === 'heading') {
                $out .= $this->renderTemplate(
                    'heading',
                    $this->partAttributes('heading', $attrs + ['role' => 'heading']),
                    ['content' => $this->text((string)($item['label'] ?? ''), $itemEscape)],
                );
                continue;
            }
            if ($type === 'group') {
                $headingId = $item['id'] ?? ($prefix . '-' . $index . '-label');
                $heading = '';
                if (!empty($item['label'])) {
                    $heading = $this->renderTemplate(
                        'heading',
                        $this->partAttributes('heading', ['role' => 'heading', 'id' => $headingId]),
                        ['content' => $this->text((string)$item['label'], $itemEscape)],
                    );
                }
                $out .= $this->renderTemplate('group', $this->cleanAttributes($attrs + [
                    'role' => 'group',
                    'aria-labelledby' => $heading !== '' ? $headingId : null,
                ]), [
                    'heading' => $heading,
                    'content' => $this->items((array)($item['items'] ?? []), $prefix . '-' . $index, $escape),
                ]);
                continue;
            }

            $role = match ($type) {
                'checkbox' => 'menuitemcheckbox',
                'radio' => 'menuitemradio',
                default => 'menuitem',
            };
            $attrs += [
                'id' => $item['id'] ?? null,
                'role' => $role,
                'aria-checked' => in_array($type, ['checkbox', 'radio'], true)
                    ? $this->bool(!empty($item['checked']))
                    : null,
                'aria-disabled' => !empty($item['disabled']) ? 'true' : null,
                'data-variant' => $item['variant'] ?? null,
                'class' => $item['class'] ?? null,
            ];
            $data = [
                'icon' => $this->Icon->optional($item['icon'] ?? null),
                'content' => $this->text((string)($item['label'] ?? ''), $itemEscape),
                'shortcut' => isset($item['shortcut'])
                    ? $this->formatTemplate('shortcut', ['content' => h((string)$item['shortcut'])])
                    : '',
            ];

            if (isset($item['url'])) {
                $data['url'] = $this->Url->build($item['url']);
                $out .= $this->renderTemplate('itemLink', $this->partAttributes('item', $attrs), $data);
            } else {
                $out .= $this->renderTemplate('item', $this->partAttributes('item', $attrs), $data);
            }
        }

        return $out;
    }
}
