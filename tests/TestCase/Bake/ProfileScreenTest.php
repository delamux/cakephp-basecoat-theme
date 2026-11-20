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
 * Renders the baked `profile` screen template with its sample data (or custom data).
 */
class ProfileScreenTest extends TestCase
{
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
     */
    private function render(?array $vars = null, array $query = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('profile', 'Profile');
        $file = TMP . 'profile_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Profile/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/profile',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Profile', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
        ]);
        Router::setRequest($request);
        $view = new View($request);
        foreach (BasecoatHelpers::MAP as $alias => $class) {
            if ($alias !== 'Form') {
                $view->loadHelper($alias, ['className' => $class]);
            }
        }
        $view->loadHelper('Form', ['className' => 'BasecoatTheme.Form']);
        $vars ??= ScreenRegistry::data('profile');

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

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('profile');

        $this->assertSame(['profile', 'stats', 'activity', 'projects'], array_keys($data));
        $this->assertCount(3, $data['stats']);
        $this->assertGreaterThanOrEqual(5, count($data['activity']));
        $this->assertGreaterThanOrEqual(5, count($data['projects']));
        $this->assertStringEndsWith('@example.com', $data['profile']['email']);
    }

    public function testIdentityAndActions(): void
    {
        $html = $this->render();

        // The visible name is the page's h2 (no extra sr-only heading).
        $this->assertMatchesRegularExpression('/<h2[^>]*>Elena Sarto<\/h2>/', $html);
        $this->assertSame(1, substr_count($html, '<h2'));
        $this->assertStringContainsString('Head of Operations', $html);
        $this->assertStringContainsString('Lisbon, Portugal', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*href="#"[^>]*>.*?Edit profile<\/a>/s', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*href="#"[^>]*>.*?Message<\/a>/s', $html);
        // "Message" uses the chat icon, not the mail icon of the Email detail.
        preg_match('/<a [^>]*>((?:(?!<\/a>).)*)Message<\/a>/s', $html, $message);
        $this->assertStringContainsString('M7.9 20A9 9 0 1 0 4 16.1L2 22Z', $message[1]);
        // The visible name is next to the avatar: initials are hidden from screen readers.
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertDoesNotMatchRegularExpression('/<h1/', $html);
    }

    public function testCoverIsACssGradientWithoutImages(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('bg-linear-to-r', $html);
        $this->assertDoesNotMatchRegularExpression('/<img|url\(|style=/', $html);
    }

    public function testFourTabsWithPanels(): void
    {
        $html = $this->render();

        $this->assertSame(4, substr_count($html, 'role="tab"'));
        $this->assertSame(4, substr_count($html, 'role="tabpanel"'));
        foreach (['Overview', 'Activity', 'Projects', 'Settings'] as $label) {
            $this->assertMatchesRegularExpression('/role="tab"[^>]*>' . $label . '<\/button>/', $html);
        }
        $this->assertSame(1, substr_count($html, 'aria-selected="true"') / 2);
        $this->assertStringContainsString('aria-label="Profile sections"', $html);
    }

    public function testOverview(): void
    {
        $html = $this->render();
        $data = ScreenRegistry::data('profile');

        foreach (['About', 'Skills', 'Details'] as $title) {
            $this->assertMatchesRegularExpression('/<h3[^>]*>' . $title . '<\/h3>/', $html);
        }
        foreach ($data['profile']['skills'] as $skill) {
            $this->assertStringContainsString('>' . $skill . '<', $html);
        }
        foreach (['Email', 'Phone', 'Time zone', 'Joined'] as $term) {
            $this->assertMatchesRegularExpression('/<dt[^>]*>' . $term . '<\/dt><dd[^>]*>[^<]+<\/dd>/', $html);
        }
        foreach ($data['stats'] as $stat) {
            $this->assertStringContainsString($stat['label'], $html);
            $this->assertStringContainsString($stat['value'], $html);
        }
        $this->assertSame(3, substr_count($html, 'data-trend='));
    }

    public function testActivityTimeline(): void
    {
        $html = $this->render();
        $data = ScreenRegistry::data('profile');

        $this->assertSame(count($data['activity']), substr_count($html, 'data-activity='));
        $this->assertStringContainsString($data['activity'][0]['text'], str_replace('&amp;', '&', $html));
    }

    public function testProjectsHaveNamedProgress(): void
    {
        $html = $this->render();
        $data = ScreenRegistry::data('profile');

        $this->assertSame(count($data['projects']), substr_count($html, 'data-project='));
        $this->assertSame(count($data['projects']), substr_count($html, 'role="progressbar"'));
        $this->assertStringContainsString('aria-label="Progress of Support playbook v2"', $html);
        $this->assertStringContainsString('aria-valuenow="60"', $html);
        // Status is text, not colour only.
        foreach (['on_track' => 'On track', 'at_risk' => 'At risk', 'planned' => 'Planned', 'done' => 'Done'] as $key => $status) {
            $this->assertMatchesRegularExpression('/data-status="' . $key . '"[^>]*>' . $status . '</', $html);
        }
        $this->assertMatchesRegularExpression('/data-variant="destructive"[^>]*data-status="at_risk"|data-status="at_risk"[^>]*data-variant="destructive"/', $html);
    }

    public function testSettingsFormPostsToAPlaceholderWithLabelledFields(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<form [^>]*method="(post|POST)"[^>]*action="#"/', $html);
        foreach (['profile-name' => 'Name', 'profile-email' => 'Email', 'profile-bio' => 'Bio'] as $id => $label) {
            $this->assertMatchesRegularExpression('/<label for="' . $id . '"[^>]*>' . $label . '<\/label>/', $html);
            $this->assertMatchesRegularExpression('/<(input|textarea) [^>]*id="' . $id . '"/', $html);
        }
        $this->assertStringContainsString('<textarea', $html);
        $this->assertSame(3, substr_count($html, 'role="switch"'));
        foreach (['mentions', 'weekly_digest', 'product_news'] as $key) {
            $this->assertMatchesRegularExpression('/<label for="profile-notify-' . $key . '"[^>]*>[^<]+<\/label>/', $html);
        }
        // Mentions and weekly digest are on, product news is off.
        $this->assertSame(2, preg_match_all('/role="switch"[^>]*checked/', $html) + preg_match_all('/checked[^>]*role="switch"/', $html));
        $this->assertMatchesRegularExpression('/<button [^>]*type="submit"[^>]*>Save changes<\/button>|<input [^>]*type="submit"[^>]*value="Save changes"/', $html);
    }

    public function testIdsAreUnique(): void
    {
        $html = $this->render();

        preg_match_all('/\sid="([^"]+)"/', $html, $ids);
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), fn (int $n): bool => $n > 1)));
    }

    public function testEmptyData(): void
    {
        $html = $this->render(['profile' => ['name' => 'Noor Haddad'], 'stats' => [], 'activity' => [], 'projects' => []]);

        $this->assertStringContainsString('No activity yet', $html);
        $this->assertStringContainsString('No projects', $html);
        $this->assertStringContainsString('No skills listed yet.', $html);
        $this->assertStringContainsString('Noor Haddad', $html);
        // No about text: the About card is skipped.
        $this->assertDoesNotMatchRegularExpression('/>About<\/h3>/', $html);
    }

    public function testEscaping(): void
    {
        $data = ScreenRegistry::data('profile');
        $data['profile']['name'] = '<script>alert(1)</script>';
        $data['profile']['about'] = '<img src=x onerror=alert(1)>';
        $data['projects'][0]['name'] = '<b>Evil</b>';
        $data['activity'][0]['text'] = '<i>x</i>';
        $html = $this->render($data);

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<b>Evil', $html);
        $this->assertStringNotContainsString('<i>x</i>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testNoLinksToMissingActionsAndNoInlineJs(): void
    {
        $html = $this->render();

        preg_match_all('/<a [^>]*href="([^"]*)"/', $html, $links);
        foreach ($links[1] as $href) {
            $this->assertSame('#', $href);
        }
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+="|<script|javascript:/i', $html);
    }

    public function testStringsAreTranslatableInTheTemplate(): void
    {
        $code = (new BakeScreenCommand())->renderFiles('profile', 'Profile', 'Demo')['templates/Profile/index.php'];

        $this->assertStringContainsString("__d('Demo', 'Edit profile')", $code);
        $this->assertStringContainsString("__d('Demo', 'Progress of {0}'", $code);
        // Stat labels and descriptions are data: never msgids built from variables.
        $this->assertDoesNotMatchRegularExpression("/__d\\('Demo', \\(string\\)\\$/", $code);
        $this->assertDoesNotMatchRegularExpression("/__d\\('Demo', \\$/", $code);
    }
}
