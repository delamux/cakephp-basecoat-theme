<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Workspace switcher: a Basecoat dropdown menu whose trigger shows the current workspace.
 *
 * ```
 * echo $this->WorkspaceSwitcher->render([
 *     ['name' => 'Northwind Labs', 'plan' => 'Pro', 'url' => '/w/northwind'],
 *     ['name' => 'Bluebird Studio', 'plan' => 'Free', 'url' => '/w/bluebird'],
 * ], ['addUrl' => ['controller' => 'Workspaces', 'action' => 'add']]);
 * ```
 *
 * @property \BasecoatTheme\View\Helper\DropdownMenuHelper $DropdownMenu
 * @property \BasecoatTheme\View\Helper\AvatarHelper $Avatar
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class WorkspaceSwitcherHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.DropdownMenu', 'BasecoatTheme.Avatar', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'classes' => [
            'trigger' => 'btn h-auto w-full justify-start gap-2 px-2 py-1.5 text-left',
            'logo' => 'rounded-md',
            'name' => 'truncate text-sm font-medium',
            'plan' => 'truncate text-xs text-muted-foreground',
        ],
        'templates' => [
            'trigger' => '{{logo}}<span class="grid flex-1 leading-tight"><span{{nameAttrs}}>{{name}}</span>{{plan}}</span>{{chevron}}',
            'plan' => '<span{{attrs}}>{{content}}</span>',
            'item' => '{{logo}}<span class="grid flex-1 leading-tight"><span class="truncate">{{name}}</span>{{plan}}</span>',
        ],
    ];

    /**
     * Renders the switcher.
     *
     * Workspaces: `name`, `plan`, `logo` (image URL), `icon` (icon name, used when there is
     * no logo), `url` and `current`. The first workspace is current when none is flagged.
     *
     * ### Options
     *
     * - `addUrl`: Adds an "add workspace" action linking there.
     * - `addLabel`: Text of that action.
     * - `label`: Heading of the workspace list.
     * - `side` / `align`: Popover position.
     * - `class` and any attribute for the dropdown root.
     *
     * @param array<array<string, mixed>> $workspaces Workspaces.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $workspaces, array $options = []): string
    {
        $workspaces = array_values($workspaces);
        if (!$workspaces) {
            return '';
        }

        $currentIndex = 0;
        foreach ($workspaces as $i => $workspace) {
            if (!empty($workspace['current'])) {
                $currentIndex = $i;
                break;
            }
        }

        $entries = [];
        foreach ($workspaces as $i => $workspace) {
            $item = [
                'type' => 'radio',
                'label' => $this->entry($workspace, 'item'),
                'checked' => $i === $currentIndex,
                'escape' => false,
            ];
            if (isset($workspace['url'])) {
                $item['url'] = $workspace['url'];
            }
            $entries[] = $item;
        }
        $items = [[
            'type' => 'group',
            'label' => h((string)($options['label'] ?? __d('basecoat_theme', 'Workspaces'))),
            'escape' => false,
            'items' => $entries,
        ]];
        if (!empty($options['addUrl'])) {
            $items[] = '-';
            $items[] = [
                'label' => h((string)($options['addLabel'] ?? __d('basecoat_theme', 'Add workspace'))),
                'url' => $options['addUrl'],
                'icon' => 'plus',
                'escape' => false,
            ];
        }

        $dropdownOptions = $this->stripOptions($options, ['addUrl', 'addLabel', 'label']);
        $dropdownOptions['escape'] = false;
        $dropdownOptions['trigger'] = [
            'variant' => 'ghost',
            'class' => $this->mergeClasses($this->getConfig('classes.trigger')),
        ];
        $dropdownOptions['class'] = $this->mergeClasses($this->getConfig('class'), $options['class'] ?? null);
        $dropdownOptions += (array)$this->getConfig('attributes');

        return $this->DropdownMenu->render(
            $this->entry($workspaces[$currentIndex], 'trigger'),
            $items,
            $dropdownOptions,
        );
    }

    /**
     * Renders the trigger content or a menu entry of a workspace (name is always escaped).
     *
     * @param array<string, mixed> $workspace Workspace.
     * @param string $template `trigger` or `item`.
     * @return string
     */
    protected function entry(array $workspace, string $template): string
    {
        $name = (string)($workspace['name'] ?? '');
        $plan = '';
        if (!empty($workspace['plan'])) {
            $plan = $this->formatTemplate('plan', [
                'attrs' => $this->formatAttributes($this->partAttributes('plan')),
                'content' => h((string)$workspace['plan']),
            ]);
        }

        return $this->formatTemplate($template, [
            'logo' => $this->logo($workspace),
            'nameAttrs' => $this->formatAttributes($this->partAttributes('name')),
            'name' => h($name),
            'plan' => $plan,
            'chevron' => $template === 'trigger'
                ? $this->Icon->render('chevrons-up-down', ['class' => 'ml-auto size-4 opacity-60'])
                : '',
        ]);
    }

    /**
     * Renders the workspace logo: image, icon or initials.
     *
     * @param array<string, mixed> $workspace Workspace.
     * @return string
     */
    protected function logo(array $workspace): string
    {
        $class = (string)$this->getConfig('classes.logo');
        if (empty($workspace['logo']) && !empty($workspace['icon'])) {
            return '<span class="inline-flex size-8 shrink-0 items-center justify-center bg-primary text-primary-foreground '
                . h($class) . '">' . $this->Icon->render((string)$workspace['icon'], ['class' => 'size-4']) . '</span>';
        }

        $options = ['class' => $class];
        if (!empty($workspace['logo'])) {
            $options['src'] = $workspace['logo'];
        }

        return $this->Avatar->render((string)($workspace['name'] ?? ''), $options);
    }
}
