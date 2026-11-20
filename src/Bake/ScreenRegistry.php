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
                'description' => 'Notification feed grouped by day, with read and unread items.',
                'controller' => 'Notifications',
                'path' => $dir . 'notifications',
                'icon' => 'bell',
            ],
        ];
    }
}
