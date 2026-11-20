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
 * Renders the baked `file-manager` screen template with its sample data (or custom data).
 */
class FileManagerScreenTest extends TestCase
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
     * @param array<string, mixed>|null $vars View variables.
     * @param array<string, mixed> $query Query string of the request.
     */
    private function render(?array $vars = null, array $query = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('file-manager', 'Files');
        $file = TMP . 'file_manager_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Files/index.php']);
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
        $this->assertSame(0, $code, 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/files',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Files', 'action' => 'index', 'pass' => []],
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
        $vars ??= ScreenRegistry::data('file-manager');

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

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('file-manager');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('file-manager');

        $this->assertSame(['path', 'storage', 'types', 'breakdown', 'folders', 'files'], array_keys($data));
        $this->assertSame(['document', 'image', 'video', 'audio', 'archive'], array_keys($data['types']));
        $this->assertGreaterThanOrEqual(5, count($data['folders']));
        $this->assertGreaterThanOrEqual(6, count($data['files']));
        foreach ($data['files'] as $file) {
            $this->assertArrayHasKey($file['type'], $data['types']);
        }
        $this->assertSame($data['storage']['used'], array_sum(array_column($data['breakdown'], 'used')));
        $this->assertLessThanOrEqual($data['storage']['total'], $data['storage']['used']);
    }

    public function testStorageOverview(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('412 GB of 1024 GB used', $html);
        $this->assertMatchesRegularExpression('/<div aria-label="Storage used"[^>]*role="progressbar" aria-valuenow="412" aria-valuemin="0" aria-valuemax="1024"/', $html);
        $this->assertSame(5, substr_count($html, 'data-storage-type='));
        $this->assertStringContainsString('aria-label="Videos storage"', $html);
        $this->assertStringContainsString('158 GB', $html);
    }

    public function testFoldersGrid(): void
    {
        $data = ScreenRegistry::data('file-manager');
        $html = $this->render();

        $this->assertSame(count($data['folders']), substr_count($html, 'data-folder='));
        $this->assertStringContainsString('142 files', $html);
        $this->assertStringContainsString('18.4 GB', $html);
        $this->assertStringContainsString('href="/files?path=Harbor+%26+Pine&amp;view=list"', $html);
    }

    public function testFilesTableWithTypeIconsAndText(): void
    {
        $data = ScreenRegistry::data('file-manager');
        $html = $this->render();

        $this->assertSame(count($data['files']), substr_count($html, '<tr data-file='));
        $this->assertStringContainsString('Q4 plan.docx', $html);
        $this->assertStringContainsString('<span class="sr-only">(Documents)</span>', $html);
        // One distinctive path of each icon: file-text, file-image, file-video, file-audio, file-archive
        foreach (['M16 17H8', 'm20 17-1.296', 'm10 11 5 3-5 3v-6Z', 'M2 19a2 2 0 1 1 4 0', 'M10 18v-2'] as $path) {
            $this->assertStringContainsString($path, $html, $path);
        }
        $this->assertStringContainsString('Elena Sarto', $html);
        $this->assertStringContainsString('684 MB', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('overflow-x-auto', $html);
    }

    public function testRowMenuIsNamedAndUsesEllipsis(): void
    {
        $data = ScreenRegistry::data('file-manager');
        $html = $this->render();

        $this->assertSame(count($data['files']), substr_count($html, '<circle cx="19" cy="12" r="1"/>'));
        $this->assertMatchesRegularExpression('/<button[^>]*aria-haspopup="menu"[^>]*>\s*<svg.*?<\/svg><span class="sr-only">Actions for Q4 plan\.docx<\/span><\/button>/s', $html);
    }

    public function testUploadIsInertAndToolbarGetsToIndex(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*Upload\s*<\/button>/s', $html);
        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/files"', $html);
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('name="type"', $html);
        $this->assertStringContainsString('<option value="archive">Archives</option>', $html);
        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertStringNotContainsString('enctype', $html);
    }

    public function testRootBreadcrumbIsOnlyTheCurrentPage(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<nav[^>]*aria-label="Current folder"[^>]*>.*<span aria-current="page">Files<\/span>/s', $html);
    }

    public function testNestedBreadcrumbAndFolderLinks(): void
    {
        $html = $this->render($this->data(['path' => 'Quillon Studio/2026 Campaigns/Q4']));

        $this->assertMatchesRegularExpression('/<a href="\/files\?view=list">Files<\/a>/', $html);
        $this->assertStringContainsString('<a href="/files?path=Quillon+Studio&amp;view=list">Quillon Studio</a>', $html);
        $this->assertStringContainsString('<a href="/files?path=Quillon+Studio%2F2026+Campaigns&amp;view=list">2026 Campaigns</a>', $html);
        $this->assertStringContainsString('<span aria-current="page">Q4</span>', $html);
        $this->assertStringContainsString('href="/files?path=Quillon+Studio%2F2026+Campaigns%2F' . 'Q4%2FBrand+assets&amp;view=list"', $html);
    }

    public function testGridView(): void
    {
        $data = ScreenRegistry::data('file-manager');
        $html = $this->render(null, ['view' => 'grid']);

        $this->assertStringContainsString('data-view="grid"', $html);
        $this->assertStringNotContainsString('<table', $html);
        $this->assertSame(count($data['files']), substr_count($html, '<li class="card flex min-w-0 flex-col'));
        $this->assertMatchesRegularExpression('/<a [^>]*aria-current="true"[^>]*>.*?Grid\s*<\/a>/s', $html);
        $this->assertStringContainsString('href="/files?view=list"', $html);
        $this->assertStringContainsString('Type: </span>Documents', $html);
    }

    public function testListIsTheDefaultAndSwitchLinksKeepPath(): void
    {
        $html = $this->render($this->data(['path' => 'Lantern Books']));

        $this->assertStringContainsString('data-view="list"', $html);
        $this->assertStringContainsString('href="/files?path=Lantern+Books&amp;view=grid"', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*aria-current="true"[^>]*>.*?List\s*<\/a>/s', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['folders' => [], 'files' => [], 'breakdown' => [], 'storage' => ['used' => 0, 'total' => 0, 'unit' => 'GB']]));

        $this->assertStringContainsString('No folders', $html);
        $this->assertStringContainsString('No files', $html);
        $this->assertStringContainsString('Upload a file to get started.', $html);
        $this->assertStringNotContainsString('<table', $html);
        $this->assertStringNotContainsString('Nothing matches', $html);
    }

    public function testEmptyWithFiltersExplainsTheFilters(): void
    {
        $html = $this->render($this->data(['files' => []]), ['q' => 'zzz']);

        $this->assertStringContainsString('Nothing matches these filters.', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render($this->data([
            'path' => '<b>Dir</b>/<i>Sub</i>',
            'folders' => [['name' => '<script>alert(1)</script>', 'files' => 1, 'size' => '<u>1 MB</u>']],
            'files' => [['id' => 1, 'name' => '<img src=x onerror=alert(2)>', 'type' => 'image', 'owner' => '<i>Eve</i>', 'size' => '<s>1 KB</s>', 'modified' => '<em>now</em>']],
            'types' => ['image' => '<u>Images</u>'],
            'breakdown' => [['type' => 'image', 'used' => 1]],
        ]));

        foreach (['<script>alert', '<img src=x', '<i>Eve', '<u>', '<s>1', '<em>now', '<b>Dir', '<i>Sub'] as $raw) {
            $this->assertStringNotContainsString($raw, $html, $raw);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $html);
    }

    public function testUniqueIds(): void
    {
        foreach ([[], ['view' => 'grid']] as $query) {
            $html = $this->render(null, $query);

            preg_match_all('/\bid="([^"]+)"/', $html, $matches);
            $this->assertNotEmpty($matches[1]);
            $this->assertSame($matches[1], array_values(array_unique($matches[1])));
        }
    }

    public function testNoLinksToMissingActions(): void
    {
        foreach ([[], ['view' => 'grid']] as $query) {
            $html = $this->render(null, $query);

            preg_match_all('/href="([^"]*)"/', $html, $matches);
            foreach ($matches[1] as $href) {
                $this->assertMatchesRegularExpression('/^(#|\/files(\?|$))/', $href, 'Unexpected link ' . $href);
            }
            $this->assertStringNotContainsString('<form method="post"', $html);
        }
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString(' style=', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/file-manager/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/file-manager/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }
}
