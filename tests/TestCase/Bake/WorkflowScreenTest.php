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
    private const VERSION = '0.40.4';

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
     * JSON of a `<script type="application/json" data-workflow-…>` block, decoded.
     */
    private function jsonBlock(string $html, string $name): mixed
    {
        $this->assertSame(1, preg_match('#<script type="application/json" data-workflow-' . $name . '>(.*?)</script>#s', $html, $m), $name);

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     * @return list<array<string, mixed>>
     */
    private function flatten(array $steps): array
    {
        $all = [];
        foreach ($steps as $step) {
            $all[] = $step;
            foreach ($step['branches'] ?? [] as $branch) {
                $all = [...$all, ...$this->flatten($branch)];
            }
        }

        return $all;
    }

    public function testRegistryEntry(): void
    {
        $screen = ScreenRegistry::get('workflow');

        $this->assertSame('Workflow', $screen['title']);
        $this->assertSame('Workflows', $screen['controller']);
        $this->assertSame('workflow', $screen['icon']);
        $this->assertSame(ROOT . '/templates/bake/Screen/workflow', $screen['path']);
        $this->assertNotSame('', $screen['description']);
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('workflow');

        $this->assertSame(['workflow', 'definition', 'toolbox', 'runs'], array_keys($data));
        $this->assertSame(['properties', 'sequence'], array_keys($data['definition']));
        $steps = $this->flatten($data['definition']['sequence']);
        $this->assertCount(7, $steps);
        $ids = array_column($steps, 'id');
        $this->assertSame($ids, array_values(array_unique($ids)), 'Step ids are unique');
        foreach ($steps as $step) {
            $this->assertContains($step['componentType'], ['task', 'switch']);
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9-]*$/', $step['type']);
            $this->assertIsArray($step['properties']);
        }
        $switch = array_values(array_filter($steps, fn(array $s): bool => $s['componentType'] === 'switch'));
        $this->assertCount(1, $switch);
        $this->assertCount(2, $switch[0]['branches']);
        $this->assertSame(['Triggers', 'Actions', 'Logic'], array_column($data['toolbox'], 'name'));
        $this->assertGreaterThanOrEqual(4, count($data['runs']));
    }

    public function testFallbackListRendersEveryStepWithBranches(): void
    {
        $data = ScreenRegistry::data('workflow');
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<div[^>]*data-workflow-fallback/', $html);
        foreach ($this->flatten($data['definition']['sequence']) as $step) {
            $this->assertStringContainsString('>' . h($step['name']) . '<', $html);
        }
        // Branches: a nested list per branch, named by the branch.
        $this->assertMatchesRegularExpression('#<ol[^>]*data-workflow-steps[^>]*>.*<ul[^>]*data-workflow-branches[^>]*>.*Yes.*<ol[^>]*data-workflow-steps[^>]*>.*Assign success manager.*No.*Add to nurture list#s', $html);
        $this->assertSame(substr_count($html, '<ol '), substr_count($html, '</ol>'));
    }

    public function testDefinitionDataBlockIsValidJsonEqualToTheData(): void
    {
        $data = ScreenRegistry::data('workflow');
        $html = $this->render();

        $this->assertSame($data['definition'], $this->jsonBlock($html, 'definition'));
        $config = $this->jsonBlock($html, 'config');
        $this->assertSame($data['toolbox'], $config['toolbox']);
        $this->assertSame('Toolbox', $config['i18n']['toolbox.title']);
        $this->assertSame('Zoom in', $config['i18n']['controlBar.zoomIn']);
        $this->assertSame('Step name', $config['i18n']['editor.stepName']);
        $this->assertArrayHasKey('subject', $config['fields']);
    }

    public function testEmptyPropertiesAreJsonObjects(): void
    {
        $html = $this->render($this->data([
            'definition' => ['properties' => [], 'sequence' => [
                ['id' => 's1', 'componentType' => 'task', 'type' => 'task', 'name' => 'Only', 'properties' => []],
            ]],
            'toolbox' => [['name' => 'Logic', 'steps' => [
                ['componentType' => 'switch', 'type' => 'if', 'name' => 'If', 'properties' => [], 'branches' => ['Yes' => [], 'No' => []]],
            ]]],
        ]));

        $this->assertMatchesRegularExpression('#data-workflow-definition>\{"properties":\{\},"sequence":\[\{[^<]*"properties":\{\}\}\]\}</script>#', $html);
        $this->assertStringContainsString('"branches":{"Yes":[],"No":[]}', $html);
    }

    public function testHiddenInputCarriesTheDefinition(): void
    {
        $data = ScreenRegistry::data('workflow');
        $html = $this->render();

        $this->assertSame(1, preg_match('/<input type="hidden" name="definition" value="([^"]*)" data-workflow-input>/', $html, $m));
        $this->assertSame($data['definition'], json_decode(html_entity_decode($m[1], ENT_QUOTES), true));
    }

    public function testAssetsArePinnedAndLoadedThroughViewBlocks(): void
    {
        $html = $this->render();
        $css = $this->view->fetch('css');
        $script = $this->view->fetch('script');

        $cdn = 'https://cdn.jsdelivr.net/npm/sequential-workflow-designer@' . self::VERSION . '/';
        foreach (['css/designer.css', 'css/designer-light.css', 'css/designer-dark.css'] as $file) {
            $this->assertStringContainsString('href="' . $cdn . $file . '"', $css);
        }
        $this->assertStringContainsString('href="/basecoat_theme/css/workflow.css"', $css);
        $this->assertLessThan(strpos($css, 'workflow.css'), strpos($css, 'designer-dark.css'), 'The restyle loads after the library CSS');
        $this->assertStringContainsString('<script src="' . $cdn . 'dist/index.umd.js" defer="defer"></script>', $script);
        $this->assertStringNotContainsString('sequential-workflow-designer@', $html, 'Assets go to the blocks, not the content');
    }

    public function testCanvasIsALabelledRegionHiddenUntilTheDesignerMounts(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<div class="workflow-canvas" data-workflow-canvas role="region" aria-label="Workflow designer" hidden><\/div>/', $html);
        $this->assertMatchesRegularExpression('/<section[^>]*data-workflow-designer[^>]*aria-labelledby="workflow-steps-title"/', $html);
        $this->assertStringContainsString('id="workflow-steps-title"', $html);
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

    public function testEmptyDefinitionAndRuns(): void
    {
        $html = $this->render($this->data(['definition' => ['properties' => [], 'sequence' => []], 'runs' => []]));

        $this->assertStringContainsString('No steps yet', $html);
        $this->assertStringContainsString('No runs yet', $html);
        $this->assertStringNotContainsString('data-workflow-steps', $html);
        $this->assertSame(['properties' => [], 'sequence' => []], $this->jsonBlock($html, 'definition'));
        $this->assertStringContainsString('data-workflow-canvas', $html, 'The designer still mounts to add the first step');
    }

    public function testEscapes(): void
    {
        $evil = '</script><script>alert(1)</script>';
        $html = $this->render($this->data([
            'workflow' => ['id' => 1, 'name' => '<b>Flow</b>', 'description' => '<i>d</i>', 'status' => 'active', 'updated' => '<u>now</u>'],
            'definition' => ['properties' => ['name' => $evil], 'sequence' => [
                ['id' => 's1', 'componentType' => 'switch', 'type' => 'if', 'name' => $evil, 'properties' => ['x' => "'\"&"], 'branches' => [
                    '<em>Yes</em>' => [['id' => 's2', 'componentType' => 'task', 'type' => 'task', 'name' => '<img src=x>', 'properties' => ['a' => 1]]],
                ]],
            ]],
            'runs' => [['id' => 'r1', 'started' => '<s>t</s>', 'trigger' => '<s>tr</s>', 'status' => '<x>', 'duration' => '<s>1</s>']],
        ]));

        foreach (['<b>Flow', '<i>d', '<u>now', '<script>alert', '<em>Yes', '<img src=x', '<s>', '<x>'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertSame(2, substr_count($html, '<script'), 'Only the two JSON data blocks');
        $this->assertSame($evil, $this->jsonBlock($html, 'definition')['sequence'][0]['name']);
    }

    public function testNoInlineJsOrStyles(): void
    {
        $html = $this->render();

        preg_match_all('/<script\b[^>]*>/', $html, $scripts);
        $this->assertSame(['<script type="application/json" data-workflow-definition>', '<script type="application/json" data-workflow-config>'], $scripts[0]);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
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
        $this->assertStringContainsString("__d('Admin', 'Toolbox')", $template);
        $this->assertStringContainsString("__d('Admin', 'No steps yet')", $template);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/workflow/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/workflow/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }

    public function testPluginScriptMountsTheDesigner(): void
    {
        $js = (string)file_get_contents(ROOT . '/webroot/js/basecoat-theme.js');

        $this->assertStringContainsString('[data-workflow-designer]', $js);
        $this->assertStringContainsString('sequentialWorkflowDesigner', $js);
        $this->assertStringContainsString('onDefinitionChanged', $js);
        $this->assertStringNotContainsString('innerHTML', $js, 'Editors build DOM with textContent');
    }

    public function testRestyleMapsBothDesignerThemesToBasecoatTokens(): void
    {
        $css = (string)file_get_contents(ROOT . '/webroot/css/workflow.css');

        $this->assertStringContainsString('.sqd-theme-light', $css);
        $this->assertStringContainsString('.sqd-theme-dark', $css);
        foreach (['--background', '--foreground', '--muted', '--border', '--primary', '--radius'] as $token) {
            $this->assertStringContainsString('var(' . $token, $css);
        }
    }
}
