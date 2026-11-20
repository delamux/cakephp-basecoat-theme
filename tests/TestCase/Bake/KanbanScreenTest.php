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
 * Renders the baked `kanban` screen template with its sample data (or custom data).
 */
class KanbanScreenTest extends TestCase
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
        $files = (new BakeScreenCommand())->renderFiles('kanban', 'Board');
        $file = TMP . 'kanban_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Board/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/board',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Board', 'action' => 'index', 'pass' => []],
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
        $view->loadHelper('NotificationList', ['className' => 'BasecoatTheme.NotificationList']);
        $vars ??= ScreenRegistry::data('kanban');

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
        return $override + ScreenRegistry::data('kanban');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('kanban');

        $this->assertSame(['columns', 'priorities', 'cards'], array_keys($data));
        $this->assertSame(['Backlog', 'In progress', 'Review', 'Done'], array_values($data['columns']));
        $this->assertGreaterThanOrEqual(8, count($data['cards']));
        foreach ($data['cards'] as $card) {
            $this->assertArrayHasKey($card['column'], $data['columns']);
            $this->assertArrayHasKey($card['priority'], $data['priorities']);
            $this->assertGreaterThanOrEqual(0, $card['progress']);
            $this->assertLessThanOrEqual(100, $card['progress']);
        }
        $this->assertSame(array_keys($data['columns']), array_values(array_unique(array_column($data['cards'], 'column'))) + [], 'Every column of the sample has cards');
    }

    public function testViewSwitchIsTabsWithBoardAndList(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/role="tab"[^>]*>Board<\/button>/', $html);
        $this->assertMatchesRegularExpression('/role="tab"[^>]*>List<\/button>/', $html);
        $this->assertSame(2, substr_count($html, 'role="tabpanel"'));
        $this->assertSame(1, substr_count($html, 'id="kanban-view-panel-list"'));
        $this->assertMatchesRegularExpression('/id="kanban-view-panel-list"[^>]*hidden/', $html, 'List is hidden until its tab is selected');
    }

    public function testColumnsAreLabelledRegionsWithCounts(): void
    {
        $data = ScreenRegistry::data('kanban');
        $html = $this->render();

        foreach ($data['columns'] as $id => $title) {
            $n = count(array_filter($data['cards'], fn(array $c) => $c['column'] === $id));
            $this->assertMatchesRegularExpression(
                '/<section[^>]*data-kanban-column="' . $id . '"[^>]*aria-labelledby="kanban-column-' . $id . '"/',
                $html,
            );
            $this->assertMatchesRegularExpression('/<h3 id="kanban-column-' . $id . '"[^>]*>' . preg_quote($title, '/') . '<\/h3>/', $html);
            $this->assertMatchesRegularExpression(
                '/data-kanban-count="' . $n . '"[^>]*><span aria-hidden="true">' . $n . '<\/span><span class="sr-only">' . $n . ' cards<\/span>/',
                $html,
            );
        }
    }

    public function testCardsAreListItemsInTheBoardAndRowsInTheList(): void
    {
        $data = ScreenRegistry::data('kanban');
        $html = $this->render();

        $this->assertSame(count($data['cards']), substr_count($html, 'data-kanban-card="'));
        $this->assertSame(count($data['columns']), substr_count($html, '<ul class="flex flex-col gap-3" data-kanban-list='));
        $this->assertSame(count($data['cards']), substr_count($html, '<tr') - 1, 'The list view has a row per card');
        foreach ($data['cards'] as $card) {
            $this->assertSame(2, substr_count($html, '>' . htmlspecialchars($card['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<'), $card['title']);
        }
    }

    public function testCardDetailsAndAccessibleCounts(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<svg[^>]*aria-hidden="true"[^>]*>.*?<\/svg><span>4<\/span><span class="sr-only">attachments<\/span>/s', $html);
        $this->assertMatchesRegularExpression('/<span>9<\/span><span class="sr-only">comments<\/span>/', $html);
        $this->assertStringContainsString('Due Nov 28', $html);
        $this->assertMatchesRegularExpression('/data-priority="high"[^>]*>\s*High\s*<\/span>/', $html);
        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('aria-label="Progress of Redesign the billing page"', $html);
        $this->assertStringContainsString('>60%</span>', $html);
        $this->assertStringContainsString('<span class="sr-only">Assigned to Elena Sarto, Mireia Vidal, Kofi Mensah</span>', $html);
        $this->assertMatchesRegularExpression('/aria-hidden="true"[^>]*class="avatar-group"|class="avatar-group"[^>]*aria-hidden="true"/', $html);
    }

    public function testIconOnlyButtonsHaveNames(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span class="sr-only">Options for Redesign the billing page<\/span>\s*<\/button>/s', $html);
        $this->assertStringContainsString('aria-label="Add card to In progress"', $html);
        $this->assertSame(4, substr_count($html, '>Add card</button>') + substr_count($html, "Add card\n"), 'One add button per column');
        $this->assertStringContainsString('New card', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['cards' => []]));

        $this->assertSame(4, substr_count($html, '>No cards<'));
        $this->assertStringContainsString('No cards yet.', $html);
        $this->assertStringNotContainsString('data-kanban-card', $html);
        $this->assertStringNotContainsString('<ul class="flex flex-col gap-3"', $html);
        $this->assertStringContainsString('<span class="sr-only">0 cards</span>', $html);
    }

    public function testCardWithoutAssigneesRenders(): void
    {
        $card = ['assignees' => [], 'progress' => 0] + ScreenRegistry::data('kanban')['cards'][0];
        $html = $this->render($this->data(['cards' => [$card]]));

        $this->assertStringNotContainsString('Assigned to', $html);
        $this->assertStringNotContainsString('avatar-group', $html);
    }

    public function testEscapes(): void
    {
        $card = [
            'id' => 1,
            'column' => 'backlog',
            'title' => '<script>alert(1)</script>',
            'description' => '<img src=x onerror=alert(2)>',
            'assignees' => ['<i>Eve</i>'],
            'progress' => 10,
            'priority' => 'high',
            'due' => '<b>soon</b>',
            'attachments' => 1,
            'comments' => 1,
        ];
        $html = $this->render($this->data([
            'cards' => [$card],
            'columns' => ['backlog' => '<u>Backlog</u>'],
            'priorities' => ['high' => '<em>High</em>'],
        ]));

        foreach (['<script>alert', '<img src=x', '<b>soon', '<i>Eve', '<u>Backlog', '<em>High'] as $raw) {
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

    public function testNoLinksToMissingActionsOrForms(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('href=', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function testDragAndDropHooksDocumented(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('data-kanban-board', $html);
        $this->assertStringContainsString('data-kanban-list="review"', $html);
        $this->assertStringNotContainsString('draggable', $html);
    }

    public function testStringsAreTranslatable(): void
    {
        $files = (new BakeScreenCommand())->renderFiles('kanban', 'Board', 'Demo');
        $this->assertStringContainsString("__d('Demo', 'New card')", $files['templates/Board/index.php']);
        $this->assertStringContainsString("__('New card')", (new BakeScreenCommand())->renderFiles('kanban', 'Board')['templates/Board/index.php']);
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/kanban/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/kanban/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }
}
