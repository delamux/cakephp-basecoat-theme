<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Basecoat Command (`.command-dialog` + `.command`). Requires Basecoat JS.
 *
 * ```
 * echo $this->CommandMenu->trigger('main-menu');
 * echo $this->CommandMenu->render([
 *     'Actions' => [
 *         ['label' => 'New project', 'url' => ['action' => 'add'], 'icon' => 'plus', 'shortcut' => 'N'],
 *     ],
 * ], ['id' => 'main-menu']);
 * ```
 *
 * Opening the dialog (click on `[data-command-open]`, Ctrl/⌘ + `data-command-hotkey`)
 * is handled by `webroot/js/basecoat-theme.js`; no inline JavaScript is emitted.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 * @property \BasecoatTheme\View\Helper\KbdHelper $Kbd
 */
class CommandMenuHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Icon', 'BasecoatTheme.Kbd'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'hotkey' => 'k',
        'classes' => [
            'command' => 'command',
            'input' => null,
            'menu' => null,
            'group' => null,
            'heading' => null,
            'item' => null,
            'trigger' => 'btn justify-between gap-6 font-normal text-muted-foreground',
        ],
        'templates' => [
            'dialog' => '<dialog{{attrs}}><div{{commandAttrs}}><header>{{icon}}<input{{inputAttrs}}></header>'
                . '<div{{menuAttrs}}>{{content}}</div></div></dialog>',
            'group' => '<div{{attrs}}><span{{headingAttrs}}>{{label}}</span>{{content}}</div>',
            'item' => '<div{{attrs}}>{{icon}}<span>{{content}}</span>{{shortcut}}</div>',
            'itemLink' => '<a href="{{url}}"{{attrs}}>{{icon}}<span>{{content}}</span>{{shortcut}}</a>',
            'shortcut' => '<span data-shortcut>{{content}}</span>',
            'trigger' => '<button{{attrs}}><span class="inline-flex items-center gap-2">{{icon}}{{label}}</span>{{kbd}}</button>',
        ],
    ];

    /**
     * Renders the command dialog.
     *
     * ### Options
     *
     * - `id`: Dialog id (generated when omitted).
     * - `placeholder`: Search input placeholder.
     * - `empty`: Text shown when nothing matches.
     * - `hotkey`: Key that opens the dialog together with Ctrl/⌘ (`data-command-hotkey`).
     * - `inputOptions`, `menuOptions`, `commandOptions`: Attributes of the parts.
     * - `class` and any attribute for the `<dialog>`.
     *
     * Group items accept `label`, `url`, `icon`, `shortcut` (string or list of keys),
     * `keywords`, `disabled`, `class` and `attributes`. Labels are always escaped.
     *
     * @param array<string, array<array<string, mixed>>> $groups Items by group label.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $groups, array $options = []): string
    {
        $id = (string)($options['id'] ?? $this->uniqueId('command-menu'));
        $placeholder = $options['placeholder'] ?? __d('basecoat_theme', 'Type a command or search…');
        $empty = $options['empty'] ?? __d('basecoat_theme', 'No results found.');
        $hotkey = $options['hotkey'] ?? $this->getConfig('hotkey');

        $content = '';
        $index = 0;
        foreach ($groups as $label => $items) {
            $index++;
            $content .= $this->group((string)$label, (array)$items, $id . '-group-' . $index);
        }

        $commandAttributes = $this->partAttributes('command', (array)($options['commandOptions'] ?? []), 'command');
        $inputAttributes = $this->partAttributes('input', (array)($options['inputOptions'] ?? []) + [
            'type' => 'text',
            'id' => $id . '-input',
            'placeholder' => (string)$placeholder,
            'aria-label' => (string)$placeholder,
            'autocomplete' => 'off',
            'autocorrect' => 'off',
            'spellcheck' => 'false',
            'aria-autocomplete' => 'list',
            'role' => 'combobox',
            'aria-expanded' => 'true',
            'aria-controls' => $id . '-menu',
        ]);
        $menuAttributes = $this->partAttributes('menu', (array)($options['menuOptions'] ?? []) + [
            'role' => 'menu',
            'id' => $id . '-menu',
            'aria-orientation' => 'vertical',
            'data-empty' => (string)$empty,
        ]);

        $options['id'] = $id;
        $options['aria-label'] ??= __d('basecoat_theme', 'Command menu');
        $options['data-command-hotkey'] = $hotkey;
        $attributes = $this->rootAttributes('command-dialog', $options, [
            'placeholder', 'empty', 'hotkey', 'inputOptions', 'menuOptions', 'commandOptions',
        ]);

        return $this->formatTemplate('dialog', [
            'attrs' => $this->formatAttributes($attributes),
            'commandAttrs' => $this->formatAttributes($commandAttributes),
            'inputAttrs' => $this->formatAttributes($inputAttributes),
            'menuAttrs' => $this->formatAttributes($menuAttributes),
            'icon' => $this->Icon->render('search'),
            'content' => $content,
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }

    /**
     * Renders the search-looking button that opens a command dialog.
     *
     * ### Options
     *
     * - `label`: Button text.
     * - `keys`: Shortcut hint shown in the `Kbd` (default `⌘K`).
     * - `class`, `variant` and any attribute for the button.
     *
     * @param string $menuId Id of the command dialog.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function trigger(string $menuId, array $options = []): string
    {
        $label = $options['label'] ?? __d('basecoat_theme', 'Search…');
        $keys = $options['keys'] ?? '⌘K';
        $options += ['variant' => 'outline', 'type' => 'button'];
        $options['data-command-open'] = $menuId;
        $options['class'] = $this->mergeClasses($this->getConfig('classes.trigger'), $options['class'] ?? null);

        $attributes = $this->cleanAttributes($this->stripOptions($options, ['label', 'keys']));
        if (isset($options['variant'])) {
            $attributes['data-variant'] = $options['variant'];
        }

        return $this->renderTemplate('trigger', $attributes, [
            'icon' => $this->Icon->render('search'),
            'label' => $this->text((string)$label),
            'kbd' => $this->Kbd->render($keys),
        ]);
    }

    /**
     * Renders one group of items.
     *
     * @param string $label Group heading.
     * @param array<array<string, mixed>> $items Items.
     * @param string $id Heading id.
     * @return string
     */
    protected function group(string $label, array $items, string $id): string
    {
        $content = '';
        foreach ($items as $item) {
            $content .= $this->item($item);
        }

        return $this->formatTemplate('group', [
            'attrs' => $this->formatAttributes($this->partAttributes('group', [
                'role' => 'group',
                'aria-labelledby' => $id,
            ])),
            'headingAttrs' => $this->formatAttributes($this->partAttributes('heading', [
                'role' => 'heading',
                'id' => $id,
            ])),
            'label' => h($label),
            'content' => $content,
        ]);
    }

    /**
     * Renders one item.
     *
     * @param array<string, mixed> $item Item.
     * @return string
     */
    protected function item(array $item): string
    {
        $label = (string)($item['label'] ?? '');
        $keywords = $item['keywords'] ?? null;
        $attributes = (array)($item['attributes'] ?? []) + [
            'role' => 'menuitem',
            'data-filter' => $label,
            'data-keywords' => $keywords !== null ? (string)$keywords : null,
            'aria-disabled' => !empty($item['disabled']) ? 'true' : null,
            'class' => $item['class'] ?? null,
        ];

        $shortcut = '';
        if (!empty($item['shortcut'])) {
            $keys = is_array($item['shortcut']) ? implode('+', $item['shortcut']) : (string)$item['shortcut'];
            $shortcut = $this->formatTemplate('shortcut', ['content' => h($keys)]);
        }
        $data = [
            'icon' => $this->Icon->optional($item['icon'] ?? null),
            'content' => h($label),
            'shortcut' => $shortcut,
        ];

        if (isset($item['url'])) {
            $data['url'] = $this->Url->build($item['url']);

            return $this->renderTemplate('itemLink', $this->partAttributes('item', $attributes), $data);
        }

        return $this->renderTemplate('item', $this->partAttributes('item', $attributes), $data);
    }
}
