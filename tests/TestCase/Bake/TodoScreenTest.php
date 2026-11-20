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
 * Renders the baked `todo` screen template with its sample data (or custom data).
 */
class TodoScreenTest extends TestCase
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
        $files = (new BakeScreenCommand())->renderFiles('todo', 'Tasks');
        $file = TMP . 'todo_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Tasks/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/tasks',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Tasks', 'action' => 'index', 'pass' => []],
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
        $vars ??= ScreenRegistry::data('todo');

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
        return $override + ScreenRegistry::data('todo');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('todo');

        $this->assertSame(['groups', 'filter', 'counts'], array_keys($data));
        $this->assertSame(['today', 'upcoming', 'later'], array_keys($data['groups']));
        $tasks = array_merge(...array_values($data['groups']));
        $this->assertGreaterThanOrEqual(8, count($tasks));
        $this->assertContains(true, array_column($tasks, 'done'));
        $this->assertSame(['high', 'low', 'medium'], $this->sorted(array_column($tasks, 'priority')));
        $this->assertSame(count($tasks), $data['counts']['all']);
        $this->assertSame(count(array_filter(array_column($tasks, 'done'))), $data['counts']['completed']);
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function sorted(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }

    public function testEveryTaskHasALabelledCheckbox(): void
    {
        $data = ScreenRegistry::data('todo');
        $html = $this->render();

        foreach (array_merge(...array_values($data['groups'])) as $task) {
            $id = 'task-' . $task['id'];
            $this->assertMatchesRegularExpression('/<input type="checkbox"[^>]*id="' . $id . '"[^>]*>/', $html);
            $this->assertMatchesRegularExpression('/<label for="' . $id . '"[^>]*>' . preg_quote(htmlspecialchars($task['title'], ENT_QUOTES), '/') . '(<span class="sr-only">Completed<\/span>)?<\/label>/', $html);
        }
        $this->assertSame(9, substr_count($html, 'type="checkbox"'));
    }

    public function testCompletedStateIsCheckedAndTextual(): void
    {
        $html = $this->render();

        $this->assertSame(2, substr_count($html, 'data-done="true"'));
        $this->assertSame(2, substr_count($html, ' checked'));
        $this->assertSame(2, substr_count($html, '<span class="sr-only">Completed</span>'));
        // Inside the label: the checkbox is announced as "<title> Completed".
        $this->assertSame(2, preg_match_all('/<label for="task-\d+"[^>]*>[^<]*<span class="sr-only">Completed<\/span><\/label>/', $html));
        preg_match_all('/<li [^>]*data-done="(true|false)"[^>]*>.*?<\/li>/s', $html, $rows, PREG_SET_ORDER);
        $this->assertCount(9, $rows);
        foreach ($rows as [$row, $done]) {
            $this->assertSame($done === 'true', str_contains($row, 'Completed</span>'));
            $this->assertSame($done === 'true', str_contains($row, 'line-through'));
        }
    }

    public function testPriorityIsATextBadge(): void
    {
        $html = $this->render();

        foreach (['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $key => $label) {
            $this->assertMatchesRegularExpression('/<span[^>]*data-priority="' . $key . '"[^>]*><span class="sr-only">Priority: <\/span>' . $label . '<\/span>/', $html);
        }
    }

    public function testRowsStackOnPhones(): void
    {
        $html = $this->render();

        // Phones: title + menu on the first row, the meta (priority, tags, due, assignee) wraps below it;
        // from `sm` up the menu moves to the end of the row.
        $this->assertSame(9, substr_count($html, '<div class="flex min-w-0 flex-1 items-center gap-3">'));
        $this->assertSame(9, substr_count($html, '<div class="order-last flex basis-full flex-wrap items-center gap-2 pl-7 sm:order-none sm:basis-auto sm:pl-0">'));
        $this->assertSame(9, preg_match_all('/<div [^>]*class="dropdown-menu[^"]*sm:order-last/', $html));
        $this->assertSame(9, substr_count($html, '<input type="checkbox" class="input"'));
    }

    public function testPageHeadingLevelAndDecorativeAvatars(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<h2[^>]*>Tasks<\/h2>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<h1/', $html);
        $this->assertDoesNotMatchRegularExpression('/<span>[A-Z]{1,2}<\/span>/', $html, 'Assignee initials are hidden: the name is read once');
    }

    public function testGroupsHaveHeadings(): void
    {
        $html = $this->render();

        foreach (['today' => 'Today', 'upcoming' => 'Upcoming', 'later' => 'Later'] as $key => $label) {
            $this->assertMatchesRegularExpression('/<section aria-labelledby="task-group-' . $key . '"[^>]*>\s*<h2 id="task-group-' . $key . '"[^>]*>\s*' . $label . '/', $html);
        }
    }

    public function testEmptyGroupIsSkipped(): void
    {
        $data = ScreenRegistry::data('todo');
        $data['groups']['later'] = [];
        $html = $this->render($data);

        $this->assertStringNotContainsString('task-group-later', $html);
        $this->assertStringContainsString('task-group-today', $html);
    }

    public function testFilterTabsMarkTheCurrentOne(): void
    {
        $html = $this->render($this->data(['filter' => 'active']), ['filter' => 'active', 'q' => 'quote']);

        $this->assertMatchesRegularExpression('/<a [^>]*aria-current="true"[^>]*data-filter="active"|<a [^>]*data-filter="active"[^>]*aria-current="true"/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="true"'));
        $this->assertStringContainsString('All (9)', $html);
        $this->assertStringContainsString('Active (7)', $html);
        $this->assertStringContainsString('Completed (2)', $html);
        $this->assertStringContainsString('href="/tasks?q=quote&amp;filter=completed"', $html);
        $this->assertStringContainsString('name="filter" value="active"', $html);
    }

    public function testSearchGetsToIndex(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/tasks"', $html);
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('Add task', $html);
    }

    public function testNamedControlsAndAssignees(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<span class="sr-only">Actions for Send the renewal quote to Lantern Books</span>', $html);
        $this->assertStringContainsString('Assigned to Elena Sarto', $html);
        $this->assertSame(9, substr_count($html, '<span class="sr-only">Due</span>'));
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render(['groups' => ['today' => [], 'upcoming' => [], 'later' => []], 'filter' => 'all', 'counts' => ['all' => 0, 'active' => 0, 'completed' => 0]]);

        $this->assertStringContainsString('No tasks', $html);
        $this->assertStringContainsString('Add a task to get started.', $html);
        $this->assertStringNotContainsString('<li', $html);
        $this->assertStringNotContainsString('type="checkbox"', $html);
    }

    public function testEmptyWithSearchExplainsTheFilters(): void
    {
        $html = $this->render(['groups' => ['today' => []], 'filter' => 'all', 'counts' => ['all' => 0, 'active' => 0, 'completed' => 0]], ['q' => 'zzz']);

        $this->assertStringContainsString('Nothing matches these filters.', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render([
            'groups' => ['today' => [[
                'id' => 1,
                'title' => '<script>alert(1)</script>',
                'done' => true,
                'priority' => '<b>high</b>',
                'due' => '<i>soon</i>',
                'assignee' => ['name' => '<u>Eve</u>'],
                'tags' => ['<em>tag</em>'],
            ]]],
            'filter' => 'all',
            'counts' => ['all' => 1, 'active' => 0, 'completed' => 1],
        ]);

        foreach (['<script>alert', '<b>high', '<i>soon', '<u>Eve', '<em>tag'] as $raw) {
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

    public function testNoLinksToMissingActionsAndNoPostForms(): void
    {
        $html = $this->render();

        preg_match_all('/href="([^"]*)"/', $html, $matches);
        foreach ($matches[1] as $href) {
            $this->assertMatchesRegularExpression('/^(#|\/tasks(\?filter=\w+)?$)/', $href, 'Unexpected link ' . $href);
        }
        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/tasks")/', $html);
    }

    public function testNoInlineJsOrStyles(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString(' style=', $html);
    }

    public function testStringsAreTranslatableAndNoThirdPartyNames(): void
    {
        $template = (string)file_get_contents(ROOT . '/templates/bake/Screen/todo/template.twig');
        $source = $template . (string)file_get_contents(ROOT . '/templates/bake/Screen/todo/data.php');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
        $this->assertStringContainsString("{{ t|raw }}'Add task')", $template);
        $this->assertStringContainsString("{{ t|raw }}'Completed')", $template);

        $files = (new BakeScreenCommand())->renderFiles('todo', 'Tasks', 'Blog');
        $this->assertStringContainsString("__d('Blog', 'Add task')", $files['templates/Tasks/index.php']);
    }
}
