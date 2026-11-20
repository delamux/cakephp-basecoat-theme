<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * List of team members with an editable role select (or a read-only role badge).
 *
 * ```
 * echo $this->MemberList->render([
 *     ['id' => 1, 'name' => 'Ana Ruiz', 'email' => 'ana@example.com', 'role' => 'owner'],
 * ], ['roles' => ['owner' => 'Owner', 'editor' => 'Editor', 'viewer' => 'Viewer']]);
 * ```
 *
 * ### Options
 *
 * - `roles`: `array<string, string>` role value => label. Without it no role is shown.
 * - `field`: Select name prefix, rendered as `{field}[{id ?? index}]` (default `roles`).
 * - `readonly`: Render the role as a badge instead of a select.
 * - `empty`: Text when there are no members.
 *
 * @property \BasecoatTheme\View\Helper\AvatarHelper $Avatar
 * @property \BasecoatTheme\View\Helper\BadgeHelper $Badge
 */
class MemberListHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Avatar', 'BasecoatTheme.Badge'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'field' => 'roles',
        'classes' => [
            'item' => null,
            'select' => null,
            'empty' => null,
        ],
        'templates' => [
            'list' => '<ul{{attrs}}>{{content}}</ul>',
            'item' => '<li{{attrs}}>{{avatar}}<div class="min-w-0 flex-1"><p class="truncate text-sm font-medium leading-none">{{name}}</p>{{email}}</div>{{role}}</li>',
            'email' => '<p class="text-muted-foreground mt-1 truncate text-xs">{{content}}</p>',
            'select' => '<select{{attrs}}>{{options}}</select>',
            'option' => '<option value="{{value}}"{{attrs}}>{{content}}</option>',
            'empty' => '<p{{attrs}}>{{content}}</p>',
        ],
    ];

    /**
     * Renders the member list.
     *
     * @param array<array<string, mixed>> $members Members.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $members, array $options = []): string
    {
        $escape = (bool)($options['escape'] ?? true);
        if (!$members) {
            return $this->renderTemplate('empty', $this->partAttributes('empty', [], 'text-muted-foreground text-sm'), [
                'content' => $this->text((string)($options['empty'] ?? __d('basecoat_theme', 'No members yet.')), $escape),
            ]);
        }

        $roles = (array)($options['roles'] ?? []);
        $field = (string)($options['field'] ?? $this->getConfig('field'));
        $readonly = (bool)($options['readonly'] ?? false);

        $items = '';
        foreach (array_values($members) as $index => $member) {
            $name = (string)($member['name'] ?? '');
            $email = !empty($member['email'])
                ? $this->formatTemplate('email', ['content' => $this->text((string)$member['email'], $escape)])
                : '';
            $role = $roles ? $this->role($member, $roles, $field . '[' . ($member['id'] ?? $index) . ']', $readonly) : '';

            $items .= $this->renderTemplate('item', $this->partAttributes('item', [], 'flex items-center gap-3 py-3'), [
                // Decorative: the name is printed next to it.
                'avatar' => $this->Avatar->render($name, ['decorative' => true] + (!empty($member['avatar']) ? ['src' => $member['avatar']] : [])),
                'name' => $this->text($name, $escape),
                'email' => $email,
                'role' => $role,
            ]);
        }

        $attributes = $this->rootAttributes('divide-y', ['role' => 'list'] + $options, ['roles', 'field', 'readonly', 'empty']);

        return $this->renderTemplate('list', $attributes, ['content' => $items], $options['templateVars'] ?? []);
    }

    /**
     * @param array<string, mixed> $member Member.
     * @param array<string, string> $roles Roles.
     * @param string $name Select name.
     * @param bool $readonly Render a badge.
     * @return string
     */
    protected function role(array $member, array $roles, string $name, bool $readonly): string
    {
        $current = isset($member['role']) ? (string)$member['role'] : null;
        if ($readonly) {
            $label = $current !== null ? ($roles[$current] ?? $current) : '';

            return $label === '' ? '' : $this->Badge->render((string)$label, ['variant' => 'secondary']);
        }

        $options = '';
        foreach ($roles as $value => $label) {
            $options .= $this->renderTemplate(
                'option',
                $current !== null && (string)$value === $current ? ['selected' => true] : [],
                ['value' => h((string)$value), 'content' => h((string)$label)],
            );
        }

        $attributes = $this->partAttributes('select', [
            'name' => $name,
            'aria-label' => __d('basecoat_theme', 'Role for {0}', (string)($member['name'] ?? '')),
        ], 'select');

        return $this->renderTemplate('select', $attributes, ['options' => $options]);
    }
}
