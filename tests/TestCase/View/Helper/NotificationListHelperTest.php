<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\NotificationListHelper;

class NotificationListHelperTest extends HelperTestCase
{
    /**
     * @return array<array<string, mixed>>
     */
    private function sample(): array
    {
        return [
            ['id' => 7, 'type' => 'access', 'title' => 'Noor Haddad asked for access', 'body' => 'Billing workspace.', 'time' => '2h ago', 'unread' => true, 'actor' => ['name' => 'Noor Haddad']],
            ['id' => 8, 'type' => 'task', 'title' => 'Export finished', 'time' => 'Yesterday', 'unread' => false],
        ];
    }

    public function testRowsTypesAndUnreadMarker(): void
    {
        $html = (new NotificationListHelper($this->View))->render($this->sample(), [
            'types' => ['access' => 'Access', 'task' => 'Task'],
        ]);

        $this->assertStringContainsString('<ul role="list" class="divide-y overflow-hidden rounded-lg border">', $html);
        $this->assertSame(1, substr_count($html, 'data-unread="true"'));
        $this->assertSame(1, substr_count($html, 'data-unread-dot'));
        $this->assertStringContainsString('Unread</span>', $html);
        $this->assertStringContainsString('bg-accent/50', $html);
        $this->assertStringContainsString('data-type="access"', $html);
        $this->assertStringContainsString('data-notification-type="task"', $html);
        $this->assertStringContainsString('>Access</span>', $html);
        $this->assertStringContainsString('2h ago', $html);
        $this->assertStringContainsString('class="avatar"', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testAcceptDeclineOnlyForActionTypes(): void
    {
        $html = (new NotificationListHelper($this->View))->render($this->sample());

        $this->assertSame(1, substr_count($html, 'data-action="accept"'));
        $this->assertSame(1, substr_count($html, 'data-action="decline"'));
        $this->assertStringContainsString('>Accept</button>', $html);
        $this->assertStringContainsString('>Decline</button>', $html);
        $this->assertStringContainsString('id="notification-accept-1" aria-labelledby="notification-accept-1 notification-title-1"', $html);
        $this->assertStringContainsString('id="notification-decline-1" aria-labelledby="notification-decline-1 notification-title-1"', $html);
        $this->assertStringContainsString('<li aria-labelledby="notification-title-1"', $html);
        $this->assertStringNotContainsString('<form', $html, 'No default action URLs: inert buttons');
        $this->assertStringNotContainsString('type="submit"', $html);
    }

    public function testActionUrlsArePostForms(): void
    {
        $html = (new NotificationListHelper($this->View))->render($this->sample(), [
            'urls' => ['accept' => '/requests/{id}/accept', 'decline' => ['controller' => 'Requests', 'action' => 'decline', '{id}']],
        ]);

        $this->assertStringContainsString('<form method="post"', $html);
        $this->assertStringContainsString('action="/requests/7/accept"', $html);
        $this->assertStringContainsString('action="/requests/decline/7"', $html);
        $this->assertStringNotContainsString('<a href="/requests', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('aria-labelledby="notification-accept-1 notification-title-1"', $html);
    }

    public function testActionUrlsKeepInertButtonWhenOnlyOneIsGiven(): void
    {
        $html = (new NotificationListHelper($this->View))->render($this->sample(), [
            'urls' => ['accept' => '/requests/{id}/accept'],
        ]);

        $this->assertSame(1, substr_count($html, '<form'));
        $this->assertStringContainsString('type="button"', $html);
    }

    public function testTypeIconIsHiddenFromScreenReaders(): void
    {
        $html = (new NotificationListHelper($this->View))->render([['type' => 'task', 'title' => 'x']]);

        $this->assertMatchesRegularExpression('/<span class="[^"]*size-8[^"]*"><svg[^>]*aria-hidden="true"/', $html);
    }

    public function testIconsOptionOverridesTheIcon(): void
    {
        $helper = new NotificationListHelper($this->View);
        $default = $helper->render([['type' => 'task', 'title' => 'x']]);
        $custom = $helper->render([['type' => 'task', 'title' => 'x']], ['icons' => ['task' => 'bell']]);

        $this->assertStringContainsString((string)$this->View->loadHelper('Icon', ['className' => 'BasecoatTheme.Icon'])->get('bell'), $custom);
        $this->assertStringNotContainsString((string)$this->View->Icon->get('bell'), $default);
        $this->assertNotSame($default, $custom);
    }

    public function testVariantsOptionOverridesTheBadgeVariant(): void
    {
        $html = (new NotificationListHelper($this->View))->render(
            [['type' => 'task', 'title' => 'x']],
            ['variants' => ['task' => 'destructive']],
        );

        $this->assertStringContainsString('data-variant="destructive"', $html);
    }

    public function testDefaultClassesSurviveConfigClass(): void
    {
        $html = (new NotificationListHelper($this->View, ['class' => 'shadow-sm']))->render($this->sample());

        $this->assertStringContainsString('class="divide-y overflow-hidden rounded-lg border shadow-sm"', $html);
    }

    public function testLinkClassIsConfigurable(): void
    {
        $html = (new NotificationListHelper($this->View, ['classes' => ['link' => 'underline']]))->render([['title' => 'Open', 'url' => '/tasks/3']]);

        $this->assertStringContainsString('<a href="/tasks/3" class="underline">', $html);
    }

    public function testCustomActionsReplaceDefaults(): void
    {
        $html = (new NotificationListHelper($this->View))->render([
            ['type' => 'access', 'title' => 'x', 'actions' => '<b class="mine">Review</b>'],
        ]);

        $this->assertStringContainsString('<b class="mine">Review</b>', $html);
        $this->assertStringNotContainsString('data-action', $html);
    }

    public function testTitleLink(): void
    {
        $html = (new NotificationListHelper($this->View))->render([['title' => 'Open', 'url' => '/tasks/3']]);

        $this->assertStringContainsString('<a href="/tasks/3" class="hover:underline">', $html);
    }

    public function testEscapes(): void
    {
        $html = (new NotificationListHelper($this->View))->render([
            ['type' => '<i>t</i>', 'title' => '<script>x</script>', 'body' => '<b>b</b>', 'time' => '<u>1</u>', 'actor' => ['name' => '<em>A</em>']],
        ], ['types' => ['<i>t</i>' => '<s>label</s>']]);

        foreach (['<script>', '<b>b', '<u>1', '<em>', '<s>label', '<i>t'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testEmpty(): void
    {
        $helper = new NotificationListHelper($this->View);

        $this->assertStringContainsString('No notifications.', $helper->render([]));
        $this->assertStringContainsString('All caught up', $helper->render([], ['empty' => 'All caught up']));
        $this->assertStringNotContainsString('<li', $helper->render([]));
    }

    public function testUniqueIdsAcrossRenders(): void
    {
        $helper = new NotificationListHelper($this->View);
        $html = $helper->render($this->sample()) . $helper->render($this->sample());

        preg_match_all('/\bid="([^"]+)"/', $html, $matches);
        $this->assertCount(8, $matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testConfigAndOptionsStyleTheRoot(): void
    {
        $html = (new NotificationListHelper($this->View, ['class' => 'rounded-none']))->render(
            $this->sample(),
            ['class' => 'max-w-xl', 'id' => 'inbox', 'icons' => ['task' => 'bell']],
        );

        $this->assertStringContainsString('rounded-none', $html);
        $this->assertStringContainsString('divide-y', $html);
        $this->assertStringContainsString('max-w-xl', $html);
        $this->assertStringContainsString('id="inbox"', $html);
        $this->assertStringNotContainsString('icons=', $html);
    }

    public function testEscapeOptionIsCastToBool(): void
    {
        $html = (new NotificationListHelper($this->View))->render([['title' => '<b>Hi</b>']], ['escape' => 0]);

        $this->assertStringContainsString('<b>Hi</b>', $html);
        $this->assertStringNotContainsString('escape=', $html);
    }
}
