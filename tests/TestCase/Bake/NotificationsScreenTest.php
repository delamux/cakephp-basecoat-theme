<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use BasecoatTheme\View\BasecoatHelpers;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Renders the baked `notifications` screen template with its sample data (or custom data).
 */
class NotificationsScreenTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
        Router::reload();
        Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);
            $routes->fallbacks();
        });
    }

    protected function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Bakes the template of the screen and renders it with `$vars` (default: the sample data).
     *
     * @param array<string, mixed>|null $vars View variables.
     * @param array<string, mixed> $query Query string of the request.
     * @param callable|null $patch Changes the baked source before it runs.
     */
    private function render(?array $vars = null, array $query = [], ?callable $patch = null): string
    {
        $files = (new BakeScreenCommand())->renderFiles('notifications', 'Notifications');
        $file = TMP . 'notifications_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $patch ? $patch($files['templates/Notifications/index.php']) : $files['templates/Notifications/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/notifications',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Notifications', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
        ]);
        Router::setRequest($request);
        $view = new View($request);
        $this->view = $view;
        foreach (BasecoatHelpers::MAP as $alias => $class) {
            if ($alias !== 'Form') {
                $view->loadHelper($alias, ['className' => $class]);
            }
        }
        $view->loadHelper('Form', ['className' => 'BasecoatTheme.Form']);
        $view->loadHelper('NotificationList', ['className' => 'BasecoatTheme.NotificationList']);
        $vars ??= ScreenRegistry::data('notifications');

        try {
            return (function () use ($file, $vars): string {
                extract($vars);
                ob_start();
                include $file;

                return (string)ob_get_clean();
            })->call($view);
        } finally {
            unlink($file);
        }
    }

    private function lint(string $file): int
    {
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

        return $code;
    }

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('notifications');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('notifications');

        $this->assertSame(['notifications', 'unreadCount', 'types', 'total', 'page', 'perPage'], array_keys($data));
        $this->assertGreaterThanOrEqual(8, count($data['notifications']));
        $this->assertSame(['task', 'message', 'team', 'access'], array_keys($data['types']));
        foreach ($data['notifications'] as $item) {
            $this->assertArrayHasKey($item['type'], $data['types']);
            $this->assertIsBool($item['unread']);
            $this->assertIsString($item['time']);
        }
        $this->assertSame(
            $data['unreadCount'],
            count(array_filter(array_column($data['notifications'], 'unread'))),
            'Unread count of the sample matches the unread rows (all unread rows are on the first page)',
        );
    }

    public function testUnreadCountBadge(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<span[^>]*role="status"[^>]*><span data-count-for="[^"]*" data-count-offset="0" data-count-hide-zero=".badge">5<\/span> unread<\/span>/', $html);
        $this->assertStringContainsString('Mark all as read', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span class="sr-only">Notification settings<\/span>\s*<\/button>/s', $html);
    }

    public function testNoUnreadDisablesMarkAllAndHidesBadge(): void
    {
        $html = $this->render($this->data([
            'unreadCount' => 0,
            'notifications' => array_map(fn(array $n) => ['unread' => false] + $n, ScreenRegistry::data('notifications')['notifications']),
        ]));

        $this->assertStringNotContainsString('data-unread-count', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*(<svg.*?<\/svg>\s*)?Mark all as read/s', $html);
    }

    public function testUnreadRowsHighlighted(): void
    {
        $data = ScreenRegistry::data('notifications');
        $html = $this->render();

        $unread = count(array_filter(array_column($data['notifications'], 'unread')));
        $this->assertSame($unread, substr_count($html, 'data-unread="true"'));
        $this->assertSame(count($data['notifications']), substr_count($html, '<li '));
        $this->assertSame($unread, substr_count($html, 'data-unread-dot'));
    }

    public function testAccessRequestHasAcceptDecline(): void
    {
        $data = ScreenRegistry::data('notifications');
        $html = $this->render();

        $requests = count(array_filter($data['notifications'], fn(array $n) => $n['type'] === 'access'));
        $this->assertGreaterThan(0, $requests);
        $this->assertSame($requests, substr_count($html, 'data-action="accept"'));
        $this->assertSame($requests, substr_count($html, 'data-action="decline"'));
        preg_match_all('/<li [^>]*data-type="([a-z]+)"[^>]*>.*?<\/li>/s', $html, $rows, PREG_SET_ORDER);
        foreach ($rows as [$row, $type]) {
            $this->assertSame($type === 'access', str_contains($row, 'data-action="accept"'), 'Row of type ' . $type);
        }
    }

    public function testTypeBadges(): void
    {
        $html = $this->render();

        foreach (['task' => 'Task', 'message' => 'Message', 'team' => 'Team', 'access' => 'Access'] as $type => $label) {
            $this->assertMatchesRegularExpression(
                '/<span[^>]*data-notification-type="' . $type . '"[^>]*>' . $label . '<\/span>/',
                $html,
            );
        }
    }

    public function testToolbarFiltersGetToIndex(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/notifications"', $html);
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('name="type"', $html);
        $this->assertStringContainsString('<option value="unread">Unread</option>', $html);
        $this->assertStringContainsString('<option value="access">Access</option>', $html);
    }

    public function testFooterCountAndPagination(): void
    {
        $html = $this->render($this->data(), ['status' => 'unread']);

        $this->assertStringContainsString('Showing 1 to 10 of 24', $html);
        $this->assertStringContainsString('href="/notifications?page=2&amp;status=unread"', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*<svg[^>]*><path d="m15 18-6-6 6-6"\/><\/svg>\s*Previous<\/button>/s', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*rel="next"[^>]*>\s*Next\s*<svg[^>]*><path d="m9 18 6-6-6-6"/s', $html, 'Same pager as the users screen: icon at the end of Next');

        $last = $this->render($this->data(['page' => 3, 'notifications' => array_slice(ScreenRegistry::data('notifications')['notifications'], 0, 4)]));
        $this->assertStringContainsString('Showing 21 to 24 of 24', $last);
        $this->assertStringContainsString('href="/notifications?page=2"', $last);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*Next\s*<svg/', $last);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['notifications' => [], 'unreadCount' => 0, 'total' => 0]));

        $this->assertStringContainsString('No notifications', $html);
        $this->assertStringContainsString('You&#039;re all caught up.', $html);
        $this->assertStringNotContainsString('Nothing matches', $html);
        $this->assertStringContainsString('Showing 0 of 0', $html);
        $this->assertStringNotContainsString('<li', $html);
        $this->assertStringNotContainsString('data-unread-count', $html);
    }

    public function testEmptyWithFiltersExplainsTheFilters(): void
    {
        $html = $this->render($this->data(['notifications' => [], 'unreadCount' => 0, 'total' => 0]), ['q' => 'zzz']);

        $this->assertStringContainsString('Nothing matches these filters.', $html);
    }

    public function testDefaultActionsAreInertButtons(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/notifications")/', $html);
        $this->assertStringContainsString('data-action="accept"', $html);
        $this->assertStringNotContainsString('type="submit" data-size="sm" class="btn">Accept', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render($this->data([
            'notifications' => [[
                'id' => 1,
                'type' => 'access',
                'title' => '<script>alert(1)</script>',
                'body' => '<img src=x onerror=alert(2)>',
                'time' => '<b>now</b>',
                'unread' => true,
                'actor' => ['name' => '<i>Eve</i>'],
            ]],
            'types' => ['access' => '<u>Access</u>'],
        ]));

        foreach (['<script>alert', '<img src=x', '<b>now', '<i>Eve', '<u>Access'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $html);
    }

    public function testUniqueIds(): void
    {
        $html = $this->render();

        preg_match_all('/\bid="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testNoLinksToMissingActions(): void
    {
        $html = $this->render();

        preg_match_all('/href="([^"]*)"/', $html, $matches);
        foreach ($matches[1] as $href) {
            $this->assertMatchesRegularExpression('/^(#|\/notifications\?page=\d)/', $href, 'Unexpected link ' . $href);
        }
        $this->assertStringNotContainsString('<form method="post"', $html);
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/notifications/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/notifications/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }

    public function testLoadsScriptsAndStyles(): void
    {
        $this->render();

        $this->assertMatchesRegularExpression('#<script src="/basecoat_theme/js/basecoat-screens\.js"[^>]*defer#', $this->view->fetch('script'));
        $this->assertStringContainsString('href="/basecoat_theme/css/interactivity.css"', $this->view->fetch('css'));
    }

    public function testRowsCarryTheBehaviourHooks(): void
    {
        $html = $this->render();

        $this->assertSame(10, substr_count($html, 'data-toggle-item="1" data-filter-item="1"'));
        $this->assertSame(10, preg_match_all('/data-filter-tags="(task|message|team|access)"/', $html));
        $this->assertSame(5, substr_count($html, 'data-state="unread"'));
        $this->assertSame(5, substr_count($html, 'data-state="read"'));
        $this->assertStringContainsString('id="notifications-root"', $html);
        $this->assertMatchesRegularExpression('/<input type="search" name="q" data-filter-input="#notifications-root"/', $html);
        $this->assertMatchesRegularExpression('/<select name="status" data-filter-facet="#notifications-root" data-filter-key="status"/', $html);
        $this->assertMatchesRegularExpression('/<select name="type" data-filter-facet="#notifications-root" data-filter-key="type"/', $html, 'Type filters on the client too');
        $this->assertSame(5, substr_count($html, 'data-toggle-unset="data-unread"'), 'Mark as read drops the stale data-unread');
        $this->assertSame(4, substr_count($html, 'data-toggle-also="answered read"'), 'Accept / Decline also mark as read');
        $this->assertStringContainsString('data-filter-empty hidden', $html);
        $this->assertStringContainsString('data-toggle-all="read" data-toggle-scope="#notifications-root"', $html);
        $this->assertSame(5, substr_count($html, 'data-toggle="read"'), 'One "Mark as read" per unread row');
        $this->assertSame(5, substr_count($html, 'data-toggle-for="read" data-toggle-hidden="on" class="bg-primary'), 'The unread dot goes away');
        $this->assertStringContainsString('data-[state=read]:bg-transparent', $html);
        $this->assertStringNotContainsString('data-endpoint', $html, 'Endpoints are opt-in');
    }

    public function testAcceptDeclineUpdateTheRow(): void
    {
        $html = $this->render();

        $this->assertSame(2, substr_count($html, 'data-toggle="accepted" data-toggle-attr="data-accepted"'));
        $this->assertSame(2, substr_count($html, 'data-toggle="declined" data-toggle-attr="data-declined"'));
        $this->assertSame(2, substr_count($html, 'data-text-on="Accepted"'));
        $this->assertSame(2, substr_count($html, 'data-text-on="Declined"'));
    }

    public function testWiredAcceptDeclineStayPostForms(): void
    {
        $html = $this->render(null, [], fn(string $code): string => str_replace(
            '$urls = [];',
            '$urls = [\'accept\' => \'/requests/{id}/accept\', \'decline\' => \'/requests/{id}/decline\'];',
            $code,
        ));

        $this->assertStringContainsString('action="/requests/1/accept"', $html);
        $this->assertStringNotContainsString('data-toggle="accepted"', $html, 'The forms are left alone so they still submit');
        $this->assertStringNotContainsString('data-text-on="Accepted"', $html);
        $this->assertStringContainsString('data-toggle="read"', $html);
    }
}
