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

use function Cake\Core\h;

/**
 * Renders the baked `workflow` screen template with its sample data (or custom data).
 */
class WorkflowScreenTest extends TestCase
{
    private ?View $view = null;

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
        $this->view = null;
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Bakes the template of the screen and renders it with `$vars` (default: the sample data).
     * The View stays in `$this->view` (asset blocks).
     *
     * @param array<string, mixed>|null $vars View variables.
     */
    private function render(?array $vars = null): string
    {
        $files = (new BakeScreenCommand())->renderFiles('workflow', 'Workflows');
        $file = TMP . 'workflow_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Workflows/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/workflows',
            'params' => ['plugin' => null, 'controller' => 'Workflows', 'action' => 'index', 'pass' => []],
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
        $this->view = $view;
        $vars ??= ScreenRegistry::data('workflow');

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
        return $override + ScreenRegistry::data('workflow');
    }

    /**
     * JSON of a `<script type="application/json" data-flow-…>` block, decoded.
     *
     * @return array<string, mixed>
     */
    private function jsonBlock(string $html, string $name): array
    {
        $this->assertSame(1, preg_match('#<script type="application/json" data-flow-' . $name . '>(.*?)</script>#s', $html, $m));

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function testRegistryEntry(): void
    {
        $screen = ScreenRegistry::get('workflow');

        $this->assertSame('Workflow', $screen['title']);
        $this->assertSame('Workflows', $screen['controller']);
        $this->assertSame('workflow', $screen['icon']);
        $this->assertSame(ROOT . '/templates/bake/Screen/workflow', $screen['path']);
        $this->assertStringContainsString('canvas', $screen['description']);
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('workflow');

        $this->assertSame(['workflow', 'flow', 'palette', 'runs'], array_keys($data));
        $this->assertCount(7, $data['flow']['nodes']);
        $this->assertCount(7, $data['flow']['edges']);
        $this->assertSame(['trigger', 'action', 'condition', 'action', 'action', 'action', 'action'], array_column($data['flow']['nodes'], 'type'));
        $this->assertSame(['Triggers', 'Actions', 'Logic'], array_column($data['palette'], 'label'));
        $this->assertGreaterThanOrEqual(4, count($data['runs']));
    }

    public function testEditorRendersTheFlowWithItsPaletteAndFieldLabels(): void
    {
        $data = ScreenRegistry::data('workflow');
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<div data-flow-editor="1" role="group" aria-label="Workflow editor" class="bc-flow-editor">/', $html);
        $flow = $this->jsonBlock($html, 'data');
        $this->assertSame(array_column($data['flow']['nodes'], 'id'), array_column($flow['nodes'], 'id'));
        $this->assertSame(['x' => -160, 'y' => 470], $flow['nodes'][3]['position']);
        $config = $this->jsonBlock($html, 'config');
        $this->assertSame(['Triggers', 'Actions', 'Logic'], array_column($config['palette'], 'label'));
        $this->assertSame('Recipient', $config['fields']['to']);
        $this->assertStringContainsString('<input type="hidden" name="flow" value="', $html);
    }

    public function testFallbackListsTheStepsInReadingOrder(): void
    {
        $html = $this->render();
        preg_match_all('/<span class="bc-flow-fallback-label">([^<]+)<\/span>/', $html, $labels);

        $this->assertSame([
            'Customer signed up',
            'Send welcome email',
            'Plan is Scale?',
            'Assign success manager',
            'Add to nurture list',
            'Wait 3 days',
            'Create follow-up task',
        ], $labels[1]);
        $this->assertStringContainsString('<li>Yes: Assign success manager</li><li>No: Add to nurture list</li>', $html);
    }

    public function testAssetsAreThePluginBundleInTheViewBlocks(): void
    {
        $html = $this->render();
        $css = $this->view->fetch('css');
        $script = $this->view->fetch('script');

        $this->assertStringContainsString('href="/basecoat_theme/css/basecoat-flow.css"', $css);
        $this->assertMatchesRegularExpression('#<script src="/basecoat_theme/js/basecoat-flow.js" defer="defer"></script>#', $script);
        $this->assertStringNotContainsString('cdn.', $html . $css . $script, 'No CDN: the editor is bundled');
        $this->assertStringNotContainsString('sequential', $html . $css . $script);
    }

    public function testHeaderShowsNameStatusAndInertActions(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('New customer onboarding', $html);
        $this->assertMatchesRegularExpression('/<span[^>]*class="badge[^"]*"[^>]*>\s*Active\s*<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*type="button"[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*Save\s*<\/button>/s', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*type="button"[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*Run test\s*<\/button>/s', $html);

        $draft = $this->render($this->data(['workflow' => ['status' => 'draft'] + ScreenRegistry::data('workflow')['workflow']]));
        $this->assertMatchesRegularExpression('/<span[^>]*class="badge[^"]*"[^>]*>\s*Draft\s*<\/span>/', $draft);
    }

    public function testSaveTodoExplainsHowToReadTheFlowInput(): void
    {
        $template = (new BakeScreenCommand())->renderFiles('workflow', 'Workflows')['templates/Workflows/index.php'];

        $this->assertStringContainsString("json_decode(\$this->request->getData('flow'), true)", $template);
    }

    public function testRunHistoryTable(): void
    {
        $data = ScreenRegistry::data('workflow');
        $html = $this->render();

        $this->assertStringContainsString('id="workflow-runs-title"', $html);
        $this->assertSame(count($data['runs']) + 1, substr_count($html, '<tr'));
        foreach ($data['runs'] as $run) {
            $this->assertStringContainsString(h($run['trigger']), $html);
            $this->assertStringContainsString(h($run['duration']), $html);
        }
        // Status is text in a badge, never colour only.
        $this->assertMatchesRegularExpression('/<span[^>]*class="badge[^"]*"[^>]*>\s*Succeeded\s*<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<span class="badge" data-variant="destructive">Failed<\/span>/', $html);
    }

    public function testEmptyFlowAndRuns(): void
    {
        $html = $this->render($this->data(['flow' => ['nodes' => [], 'edges' => []], 'runs' => []]));

        $this->assertStringContainsString('No steps yet.', $html);
        $this->assertStringContainsString('No runs yet', $html);
        $this->assertSame(['nodes' => [], 'edges' => []], $this->jsonBlock($html, 'data'));
        $this->assertStringContainsString('data-flow-canvas', $html, 'The editor still mounts to add the first step');
    }

    public function testEscapes(): void
    {
        $evil = '</script><script>alert(1)</script>';
        $html = $this->render($this->data([
            'workflow' => ['id' => 1, 'name' => '<b>Flow</b>', 'description' => '<i>d</i>', 'status' => 'active', 'updated' => '<u>now</u>'],
            'flow' => ['nodes' => [
                ['id' => 's1', 'type' => 'condition', 'label' => $evil, 'position' => ['x' => 0, 'y' => 0], 'outputs' => ['y' => '<em>Yes</em>']],
                ['id' => 's2', 'type' => 'action', 'label' => '<img src=x>', 'position' => ['x' => 0, 'y' => 150], 'properties' => ['a' => "'\"&"]],
            ], 'edges' => [['source' => 's1', 'output' => 'y', 'target' => 's2']]],
            'runs' => [['id' => 'r1', 'started' => '<s>t</s>', 'trigger' => '<s>tr</s>', 'status' => '<x>', 'duration' => '<s>1</s>']],
        ]));

        foreach (['<b>Flow', '<i>d', '<u>now', '<script>alert', '<em>Yes', '<img src=x', '<s>', '<x>'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertSame(2, substr_count($html, '<script'), 'Only the two JSON data blocks');
        $this->assertSame($evil, $this->jsonBlock($html, 'data')['nodes'][0]['label']);
    }

    public function testNoInlineJsOrStyles(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('style=', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/', $html);
        preg_match_all('/<script[^>]*>/', $html, $tags);
        $this->assertSame(['<script type="application/json" data-flow-data>', '<script type="application/json" data-flow-config>'], $tags[0]);
    }

    public function testUniqueIds(): void
    {
        $html = $this->render();

        preg_match_all('/\bid="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testNoLinksToMissingActionsAndNoStateChangingForms(): void
    {
        $html = $this->render();

        preg_match_all('/href="([^"]*)"/', $html, $matches);
        foreach ($matches[1] as $href) {
            $this->assertSame('#', $href, 'Unexpected link ' . $href);
        }
        $this->assertStringNotContainsString('<form', $html);
    }

    public function testTranslatableStrings(): void
    {
        $files = (new BakeScreenCommand())->renderFiles('workflow', 'Workflows', 'Admin');
        $template = $files['templates/Workflows/index.php'];

        $this->assertStringContainsString("__d('Admin', 'Run test')", $template);
        $this->assertStringContainsString("__d('Admin', 'Workflow editor')", $template);
        $this->assertStringContainsString("__d('Admin', 'Recipient')", $template);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/workflow/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/workflow/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }
}
