<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\ScreenRegistry;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Screens available to `bin/cake bake screen`.
 */
class ScreenRegistryTest extends TestCase
{
    private const DEMO = ROOT . '/tests/test_app/templates/bake/Screen/demo';

    protected function tearDown(): void
    {
        ScreenRegistry::reset();
        parent::tearDown();
    }

    public function testAllHasBuiltIns(): void
    {
        $all = ScreenRegistry::all();
        $this->assertArrayHasKey('users', $all);
        $this->assertArrayHasKey('notifications', $all);
        $this->assertSame('Users', $all['users']['title']);
        $this->assertSame('Users', $all['users']['controller']);
        $this->assertSame('Notifications', $all['notifications']['title']);
        $this->assertSame('Notifications', $all['notifications']['controller']);
        $this->assertSame(ROOT . '/templates/bake/Screen/users', $all['users']['path']);
        $this->assertSame(ROOT . '/templates/bake/Screen/notifications', $all['notifications']['path']);
        $this->assertNotSame('', $all['users']['description']);
        $this->assertNotSame('', $all['notifications']['description']);
    }

    public function testBatchTwoBuiltIns(): void
    {
        $expected = ['chats' => ['Chats', 'message-circle'], 'ai-chat' => ['Assistant', 'sparkles'], 'mail' => ['Mail', 'mail'],
            'todo' => ['Tasks', 'list-checks'], 'kanban' => ['Board', 'columns-2'], 'notes' => ['Notes', 'sticky-note']];
        $all = ScreenRegistry::all();
        $this->assertSame(['users', 'notifications', ...array_keys($expected)], array_keys($all));
        foreach ($expected as $slug => [$controller, $icon]) {
            $this->assertSame($controller, $all[$slug]['controller'], $slug);
            $this->assertSame($icon, $all[$slug]['icon'], $slug);
            $this->assertSame(ROOT . '/templates/bake/Screen/' . $slug, $all[$slug]['path'], $slug);
            $this->assertNotSame('', $all[$slug]['title']);
            $this->assertNotSame('', $all[$slug]['description']);
        }
    }

    public function testOptionalIcon(): void
    {
        $this->assertSame('users', ScreenRegistry::get('users')['icon']);
        $this->assertSame('bell', ScreenRegistry::get('notifications')['icon']);

        $screen = ['title' => 'Demo', 'description' => 'Fixture screen.', 'controller' => 'Demo', 'path' => self::DEMO];
        ScreenRegistry::register('demo', $screen);
        $this->assertArrayNotHasKey('icon', ScreenRegistry::get('demo'));
        ScreenRegistry::register('demo', $screen + ['icon' => 'inbox']);
        $this->assertSame('inbox', ScreenRegistry::get('demo')['icon']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('icon');
        ScreenRegistry::register('demo', $screen + ['icon' => '']);
    }

    public function testGetUnknownThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown screen `nope`.*users.*notifications/s');
        ScreenRegistry::get('nope');
    }

    public function testRegisterGetDataAndReset(): void
    {
        ScreenRegistry::register('demo', [
            'title' => 'Demo',
            'description' => 'Fixture screen.',
            'controller' => 'Demo',
            'path' => self::DEMO,
        ]);
        $this->assertSame('Demo', ScreenRegistry::get('demo')['title']);
        $this->assertSame(['items'], array_keys(ScreenRegistry::data('demo')));

        ScreenRegistry::reset();
        $this->assertArrayNotHasKey('demo', ScreenRegistry::all());
        $this->assertArrayHasKey('users', ScreenRegistry::all());
    }

    public function testRegisterRejectsIncompleteScreen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScreenRegistry::register('broken', ['title' => 'Broken']);
    }
}
