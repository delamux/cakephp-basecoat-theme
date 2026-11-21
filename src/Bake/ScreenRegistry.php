<?php
declare(strict_types=1);

namespace BasecoatTheme\Bake;

use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Screens `bin/cake bake screen <slug>` can bake: a controller with sample data plus an `index`
 * template. Each screen is a directory (`path`) with:
 *
 * - `template.twig`: the view, rendered with Bake's Twig view (see BakeScreenCommand::renderFiles()).
 * - `data.php`: returns `array<string, mixed>` (view variable => sample value), written into the
 *   generated controller as PHP literals.
 *
 * Built-in screens live in `templates/bake/Screen/<slug>/`. Other plugins (or an app bootstrap)
 * can add screens with register().
 */
final class ScreenRegistry
{
    /**
     * @var array<string, array{title: string, description: string, controller: string, path: string, icon?: string}>|null
     */
    private static ?array $screens = null;

    /**
     * @return array<string, array{title: string, description: string, controller: string, path: string, icon?: string}>
     */
    public static function all(): array
    {
        return self::$screens ??= self::builtIns();
    }

    /**
     * @param string $slug Screen slug.
     * @return array{title: string, description: string, controller: string, path: string, icon?: string}
     * @throws \InvalidArgumentException For an unknown slug (the message lists the valid ones).
     */
    public static function get(string $slug): array
    {
        $screens = self::all();
        if (!isset($screens[$slug])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown screen `%s`. Available screens: %s.',
                $slug,
                implode(', ', array_keys($screens)),
            ));
        }

        return $screens[$slug];
    }

    /**
     * Sample data of a screen (its `data.php`): view variable name => value.
     *
     * @param string $slug Screen slug.
     * @return array<string, mixed>
     * @throws \UnexpectedValueException When `data.php` is missing or does not return an array.
     */
    public static function data(string $slug): array
    {
        $file = self::get($slug)['path'] . DIRECTORY_SEPARATOR . 'data.php';
        if (!is_file($file)) {
            throw new UnexpectedValueException(sprintf('Screen `%s` has no `%s`.', $slug, $file));
        }
        $data = (static fn (string $file): mixed => require $file)($file);
        if (!is_array($data)) {
            throw new UnexpectedValueException(sprintf('`%s` must return an array.', $file));
        }

        return $data;
    }

    /**
     * Adds (or replaces) a screen.
     *
     * @param string $slug Screen slug (`[a-z0-9_-]+`).
     * @param array<string, mixed> $screen `title`, `description`, `controller` (default controller
     *   name) and `path` (directory with `template.twig` and `data.php`), all non-empty strings;
     *   optional `icon` (IconHelper name, used e.g. by the catalog navigation).
     * @return void
     * @throws \InvalidArgumentException For an invalid slug, a missing key or an empty `icon`.
     */
    public static function register(string $slug, array $screen): void
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug)) {
            throw new InvalidArgumentException(sprintf('Invalid screen slug `%s`.', $slug));
        }
        $valid = [];
        foreach (['title', 'description', 'controller', 'path'] as $key) {
            if (!isset($screen[$key]) || !is_string($screen[$key]) || $screen[$key] === '') {
                throw new InvalidArgumentException(sprintf('Screen `%s` needs a non-empty `%s`.', $slug, $key));
            }
            $valid[$key] = $screen[$key];
        }
        if (array_key_exists('icon', $screen)) {
            if (!is_string($screen['icon']) || $screen['icon'] === '') {
                throw new InvalidArgumentException(sprintf('Screen `%s`: `icon` must be a non-empty string.', $slug));
            }
            $valid['icon'] = $screen['icon'];
        }
        self::all();
        self::$screens[$slug] = $valid;
    }

    /**
     * Back to the built-in screens only (tests).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$screens = null;
    }

    /**
     * @return array<string, array{title: string, description: string, controller: string, path: string, icon?: string}>
     */
    private static function builtIns(): array
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'bake'
            . DIRECTORY_SEPARATOR . 'Screen' . DIRECTORY_SEPARATOR;

        return [
            'users' => [
                'title' => 'Users',
                'description' => 'User list with search, filters and a paginated table.',
                'controller' => 'Users',
                'path' => $dir . 'users',
                'icon' => 'users',
            ],
            'notifications' => [
                'title' => 'Notifications',
                'description' => 'Notification inbox with unread markers, search and filters, and Accept / Decline for access requests.',
                'controller' => 'Notifications',
                'path' => $dir . 'notifications',
                'icon' => 'bell',
            ],
            'chats' => [
                'title' => 'Chats',
                'description' => 'Two-pane messenger: conversation list with unread counts and a message thread with a composer.',
                'controller' => 'Chats',
                'path' => $dir . 'chats',
                'icon' => 'message-circle',
            ],
            'ai-chat' => [
                'title' => 'AI chat',
                'description' => 'Assistant chat with prompt suggestions, formatted answers and a composer with a model picker.',
                'controller' => 'Assistant',
                'path' => $dir . 'ai-chat',
                'icon' => 'sparkles',
            ],
            'mail' => [
                'title' => 'Mail',
                'description' => 'Three-pane mailbox: folders and labels, message list with search, and a reading pane.',
                'controller' => 'Mail',
                'path' => $dir . 'mail',
                'icon' => 'mail',
            ],
            'todo' => [
                'title' => 'Tasks',
                'description' => 'Task list grouped by due date with filters, priorities, assignees and tags.',
                'controller' => 'Tasks',
                'path' => $dir . 'todo',
                'icon' => 'list-checks',
            ],
            'kanban' => [
                'title' => 'Board',
                'description' => 'Kanban board with columns, cards with assignees, progress and due dates, and a list view.',
                'controller' => 'Board',
                'path' => $dir . 'kanban',
                'icon' => 'columns-2',
            ],
            'notes' => [
                'title' => 'Notes',
                'description' => 'Notes workspace with labels, search and a grid of text, checklist and image notes.',
                'controller' => 'Notes',
                'path' => $dir . 'notes',
                'icon' => 'sticky-note',
            ],
            'bookings' => [
                'title' => 'Bookings',
                'description' => 'Day schedule of classes per room and coach, with date navigation and a list view.',
                'controller' => 'Bookings',
                'path' => $dir . 'bookings',
                'icon' => 'calendar-clock',
            ],
            'calendar' => [
                'title' => 'Calendar',
                'description' => 'Calendar with month, week and day views: categorised events, hours as rows, a column per person in the day view and an upcoming list.',
                'controller' => 'Calendar',
                'path' => $dir . 'calendar',
                'icon' => 'calendar-days',
            ],
            'profile' => [
                'title' => 'Profile',
                'description' => 'User profile with cover, details, skills, activity timeline, projects and settings tabs.',
                'controller' => 'Profile',
                'path' => $dir . 'profile',
                'icon' => 'circle-user',
            ],
            'onboarding' => [
                'title' => 'Onboarding',
                'description' => 'Five-step onboarding flow with a step indicator and no-JS navigation.',
                'controller' => 'Onboarding',
                'path' => $dir . 'onboarding',
                'icon' => 'rocket',
            ],
            'file-manager' => [
                'title' => 'File manager',
                'description' => 'Storage overview, folders, recent files, search and type filter.',
                'controller' => 'Files',
                'path' => $dir . 'file-manager',
                'icon' => 'folder',
            ],
            'workflow' => [
                'title' => 'Workflow',
                'description' => 'Automation editor: steps on a canvas with branches, a step panel and the run history.',
                'controller' => 'Workflows',
                'path' => $dir . 'workflow',
                'icon' => 'workflow',
            ],
        ];
    }
}
