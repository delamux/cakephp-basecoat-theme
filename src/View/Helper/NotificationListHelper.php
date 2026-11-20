<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Inbox list of notifications: a type icon (or the actor's avatar), title, unread dot, body, type
 * badge and relative time. Unread rows are highlighted and carry `data-unread="true"`; some types
 * (by default `access`) get inline Accept / Decline buttons.
 *
 * ```
 * echo $this->NotificationList->render([
 *     [
 *         'id' => 7, 'type' => 'access', 'title' => 'Noor Haddad asked for access to Billing',
 *         'body' => 'She is on the finance team.', 'time' => '2h ago', 'unread' => true,
 *         'actor' => ['name' => 'Noor Haddad'],
 *     ],
 * ], [
 *     'types' => ['task' => 'Task', 'message' => 'Message', 'team' => 'Team', 'access' => 'Access'],
 * ]);
 * ```
 *
 * ### Notification keys
 *
 * - `id`: Optional, used in the `urls` placeholders.
 * - `type`: Key of the `types` option (and of the icons and badge variants).
 * - `title`, `body`, `time`: Texts (escaped unless `escape` is false).
 * - `unread`: Highlights the row and shows the unread dot.
 * - `actor`: `['name' => ..., 'avatar' => ...]`. Shown as an avatar instead of the type icon.
 * - `url`: Makes the title a link.
 * - `actions`: HTML shown instead of the default Accept / Decline buttons (not escaped).
 *
 * ### Options
 *
 * - `types`: `array<string, string>` type => label of the badge. A type without a label shows its key.
 * - `icons`, `variants`: Override the icon / badge variant per type (merged with the config).
 * - `urls`: `['accept' => '/requests/{id}/accept', 'decline' => ...]` (strings or route arrays; `{id}` is
 *   replaced by the URL-encoded id inside strings). With a URL the button is a POST form button
 *   (`FormHelper::postButton()`, so it carries the CSRF token and needs no JS); without it the button is
 *   inert (`type="button"`). State changes are never sent with GET.
 * - `empty`: Text when there are no notifications.
 * - `escape`: Escape the texts (default true).
 *
 * Config `actionTypes` lists the types that get Accept / Decline buttons.
 *
 * @property \BasecoatTheme\View\Helper\FormHelper $Form
 * @property \BasecoatTheme\View\Helper\AvatarHelper $Avatar
 * @property \BasecoatTheme\View\Helper\BadgeHelper $Badge
 * @property \BasecoatTheme\View\Helper\ButtonHelper $Button
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class NotificationListHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Form', 'Url', 'BasecoatTheme.Avatar', 'BasecoatTheme.Badge', 'BasecoatTheme.Button', 'BasecoatTheme.Icon'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'actionTypes' => ['access'],
        'icons' => [
            'task' => 'list',
            'message' => 'mail',
            'team' => 'users',
            'access' => 'user',
        ],
        'variants' => [
            'task' => 'outline',
            'message' => 'secondary',
            'team' => 'outline',
            'access' => 'primary',
        ],
        'classes' => [
            'item' => 'flex items-start gap-3 p-4',
            'unread' => 'bg-accent/50',
            'media' => 'bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-full',
            'main' => 'flex min-w-0 flex-1 flex-col gap-1.5',
            'header' => 'flex items-center gap-2',
            'title' => 'min-w-0 flex-1 text-sm font-medium',
            'link' => 'hover:underline',
            'dot' => 'bg-primary size-2 shrink-0 rounded-full',
            'time' => 'text-muted-foreground shrink-0 text-xs whitespace-nowrap',
            'body' => 'text-muted-foreground text-sm',
            'footer' => 'flex flex-wrap items-center gap-2 pt-1',
            'empty' => 'text-muted-foreground text-sm',
        ],
        'templates' => [
            'list' => '<ul{{attrs}}>{{content}}</ul>',
            'item' => '<li{{attrs}}>{{media}}<div{{mainAttrs}}>{{header}}{{body}}{{footer}}</div></li>',
            'media' => '<span{{attrs}}>{{content}}</span>',
            'header' => '<div{{attrs}}>{{dot}}{{title}}{{time}}</div>',
            'title' => '<p{{attrs}}>{{content}}</p>',
            'titleLink' => '<p{{attrs}}><a href="{{url}}"{{linkAttrs}}>{{content}}</a></p>',
            'dot' => '<span{{attrs}}><span class="sr-only">{{label}}</span></span>',
            'time' => '<span{{attrs}}>{{content}}</span>',
            'body' => '<p{{attrs}}>{{content}}</p>',
            'footer' => '<div{{attrs}}>{{content}}</div>',
            'empty' => '<p{{attrs}}>{{content}}</p>',
        ],
    ];

    /**
     * Renders the list of notifications.
     *
     * @param array<array<string, mixed>> $notifications Notifications.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $notifications, array $options = []): string
    {
        $escape = (bool)($options['escape'] ?? true);
        if (!$notifications) {
            return $this->renderTemplate('empty', $this->partAttributes('empty'), [
                'content' => $this->text((string)($options['empty'] ?? __d('basecoat_theme', 'No notifications.')), $escape),
            ]);
        }

        $items = '';
        foreach (array_values($notifications) as $notification) {
            $items .= $this->item($notification, $options, $escape);
        }

        $attributes = $this->rootAttributes('divide-y overflow-hidden rounded-lg border', ['role' => 'list'] + $options, ['types', 'icons', 'variants', 'urls', 'empty']);

        return $this->renderTemplate('list', $attributes, ['content' => $items], $options['templateVars'] ?? []);
    }

    /**
     * @param array<string, mixed> $notification Notification.
     * @param array<string, mixed> $options Options.
     * @param bool $escape Escape the texts.
     * @return string
     */
    protected function item(array $notification, array $options, bool $escape): string
    {
        $type = (string)($notification['type'] ?? '');
        $unread = !empty($notification['unread']);
        $titleId = $this->uniqueId('notification-title');

        $dot = $unread
            ? $this->renderTemplate('dot', $this->partAttributes('dot', ['data-unread-dot' => 'true']), [
                'label' => h(__d('basecoat_theme', 'Unread')),
            ])
            : '';
        $titleText = $this->text((string)($notification['title'] ?? ''), $escape);
        $titleAttributes = $this->partAttributes('title', ['id' => $titleId]);
        $title = !empty($notification['url'])
            ? $this->renderTemplate('titleLink', $titleAttributes, [
                'url' => $this->Url->build($notification['url']),
                'linkAttrs' => $this->formatAttributes($this->partAttributes('link')),
                'content' => $titleText,
            ])
            : $this->renderTemplate('title', $titleAttributes, ['content' => $titleText]);
        $time = ($notification['time'] ?? '') !== ''
            ? $this->renderTemplate('time', $this->partAttributes('time'), ['content' => $this->text((string)$notification['time'], $escape)])
            : '';
        $header = $this->renderTemplate('header', $this->partAttributes('header'), [
            'dot' => $dot,
            'title' => $title,
            'time' => $time,
        ]);

        $body = ($notification['body'] ?? '') !== ''
            ? $this->renderTemplate('body', $this->partAttributes('body'), ['content' => $this->text((string)$notification['body'], $escape)])
            : '';

        $footerContent = $this->badge($type, $options, $escape) . $this->actions($notification, $type, $titleId, $options);
        $footer = $footerContent !== ''
            ? $this->renderTemplate('footer', $this->partAttributes('footer'), ['content' => $footerContent])
            : '';

        $attributes = $this->partAttributes('item', [
            'aria-labelledby' => $titleId,
            'data-type' => $type !== '' ? $type : null,
            'data-unread' => $unread ? 'true' : null,
            'class' => $unread ? $this->getConfig('classes.unread') : null,
        ]);

        return $this->renderTemplate('item', $attributes, [
            'media' => $this->media($notification, $type, $options),
            'mainAttrs' => $this->formatAttributes($this->partAttributes('main')),
            'header' => $header,
            'body' => $body,
            'footer' => $footer,
        ]);
    }

    /**
     * @param array<string, mixed> $notification Notification.
     * @param string $type Type.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function media(array $notification, string $type, array $options): string
    {
        $actor = (array)($notification['actor'] ?? []);
        if (($actor['name'] ?? '') !== '') {
            $name = (string)$actor['name'];
            // Decorative when the title already names the actor ("Noor Haddad asked for access…").
            $decorative = str_contains((string)($notification['title'] ?? ''), $name);

            return $this->Avatar->render($name, ['decorative' => $decorative] + (!empty($actor['avatar']) ? ['src' => $actor['avatar']] : []));
        }

        $icons = (array)($options['icons'] ?? []) + (array)$this->getConfig('icons');

        return $this->renderTemplate('media', $this->partAttributes('media'), [
            'content' => $this->Icon->render((string)($icons[$type] ?? 'bell')),
        ]);
    }

    /**
     * @param string $type Type.
     * @param array<string, mixed> $options Options.
     * @param bool $escape Escape the label.
     * @return string
     */
    protected function badge(string $type, array $options, bool $escape): string
    {
        if ($type === '') {
            return '';
        }
        $types = (array)($options['types'] ?? []);
        $variants = (array)($options['variants'] ?? []) + (array)$this->getConfig('variants');

        return $this->Badge->render((string)($types[$type] ?? $type), [
            'variant' => $variants[$type] ?? 'outline',
            'escape' => $escape,
            'data-notification-type' => $type,
        ]);
    }

    /**
     * Accept / Decline buttons, or the custom `actions` HTML of the notification.
     *
     * @param array<string, mixed> $notification Notification.
     * @param string $type Type.
     * @param string $titleId DOM id of the title (part of the accessible name of the buttons).
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function actions(array $notification, string $type, string $titleId, array $options): string
    {
        if (($notification['actions'] ?? '') !== '') {
            return (string)$notification['actions'];
        }
        if (!in_array($type, (array)$this->getConfig('actionTypes'), true)) {
            return '';
        }

        $urls = (array)($options['urls'] ?? []);
        $buttons = ['accept' => [__d('basecoat_theme', 'Accept'), null], 'decline' => [__d('basecoat_theme', 'Decline'), 'outline']];
        $out = '';
        foreach ($buttons as $action => [$label, $variant]) {
            $buttonId = $this->uniqueId('notification-' . $action);
            $attributes = [
                'id' => $buttonId,
                'size' => 'sm',
                'variant' => $variant,
                'aria-labelledby' => $buttonId . ' ' . $titleId,
                'data-action' => $action,
            ];
            $url = $urls[$action] ?? null;
            $out .= $url !== null
                ? $this->Form->postButton($label, $this->fillId($url, (string)($notification['id'] ?? '')), $attributes)
                : $this->Button->render($label, $attributes);
        }

        return $out;
    }

    /**
     * Replaces `{id}` in a URL string, or in every string of a route array.
     *
     * @param array|string $url URL.
     * @param string $id Notification id.
     * @return array|string
     */
    protected function fillId(array|string $url, string $id): array|string
    {
        $replace = fn(string $value): string => str_replace('{id}', rawurlencode($id), $value);
        if (is_string($url)) {
            return $replace($url);
        }
        array_walk_recursive($url, function (mixed &$value) use ($replace): void {
            if (is_string($value)) {
                $value = $replace($value);
            }
        });

        return $url;
    }
}
