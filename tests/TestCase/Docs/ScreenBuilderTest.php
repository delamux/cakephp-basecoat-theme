<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Docs;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use BasecoatTheme\Docs\BuildReport;
use BasecoatTheme\Docs\CatalogBuilder;
use BasecoatTheme\Docs\ScreenBuilder;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Screens section of the static catalog (bake output + live preview).
 */
class ScreenBuilderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/fixtures/';

    private const REPO = __DIR__ . '/../../..';

    private const BADGE = [
        'badge' => ['alias' => 'Badge', 'title' => 'Badge', 'group' => 'Data display', 'intro' => 'Small status labels.'],
    ];

    private string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
        ScreenRegistry::reset();
        $screen = fn(string $title, string $controller, string $path): array => [
            'title' => $title,
            'description' => $title . ' fixture screen.',
            'controller' => $controller,
            'path' => $path,
        ];
        ScreenRegistry::register('demo', $screen('Demo', 'Demo', self::REPO . '/tests/test_app/templates/bake/Screen/demo'));
        ScreenRegistry::register('broken', $screen('Broken', 'Broken', self::FIXTURES . 'screens/broken'));
        ScreenRegistry::register('linked', $screen('Linked', 'TeamInbox', self::FIXTURES . 'screens/linked'));
        ScreenRegistry::register('pending', $screen('Pending', 'Pending', self::FIXTURES . 'screens/not-there'));
        $this->out = self::REPO . '/build/screens-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ScreenRegistry::reset();
        $this->clearPlugins();
        if (is_dir($this->out)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->out, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->out);
        }
    }

    /**
     * @param list<string> $slugs Registered screens to include.
     */
    private function build(array $slugs = ['demo'], bool $strict = false): BuildReport
    {
        $screens = new ScreenBuilder(array_intersect_key(ScreenRegistry::all(), array_flip($slugs)));
        $builder = new CatalogBuilder(self::FIXTURES . 'examples', self::FIXTURES . 'pages', self::BADGE, $screens);

        return $builder->build($this->out, $strict);
    }

    private function page(string $path): string
    {
        return (string)file_get_contents($this->out . '/' . $path);
    }

    public function testBuildsScreenPages(): void
    {
        $report = $this->build();

        foreach (['screens/index.html', 'screens/demo.html', 'screens/demo/preview.html'] as $path) {
            $this->assertContains($path, $report->pages);
            $this->assertFileExists($this->out . '/' . $path);
        }
        $index = $this->page('screens/index.html');
        $this->assertStringContainsString('href="../screens/demo.html"', $index);
        $this->assertMatchesRegularExpression('#<a href="\.\./screens/demo\.html" class="catalog-tile" aria-labelledby="screen-demo-title">.*<h2 id="screen-demo-title">Demo</h2>#s', $index, 'The tile link is named by the screen title');
        $this->assertStringContainsString('Demo fixture screen.', $index);
        $this->assertSame([], $report->warnings);
        $this->assertDirectoryDoesNotExist($this->out . '/.bake', 'Scratch files removed');
    }

    public function testPreviewUsesThemeLayout(): void
    {
        $this->build();

        $html = $this->page('screens/demo/preview.html');
        $this->assertMatchesRegularExpression('/<aside[^>]*class="[^"]*\bsidebar\b/', $html);
        $this->assertStringContainsString('<h1 data-screen="demo">Demo</h1>', $html);
        $this->assertStringContainsString('Café &lt;b&gt;&quot;x&quot;&lt;/b&gt; &#039;q&#039; \\ (€12)', $html);
        $this->assertStringContainsString('src="../../assets/basecoat-theme.js"', $html);
    }

    public function testEmbedPreviewHasNoAppSidebar(): void
    {
        $report = $this->build();

        $this->assertContains('screens/demo/embed.html', $report->pages);
        $html = $this->page('screens/demo/embed.html');
        $this->assertStringNotContainsString('<aside', $html);
        $this->assertStringNotContainsString('data-sidebar-toggle', $html);
        $this->assertStringContainsString('<h1 class="truncate text-base font-medium">', $html);
        $this->assertStringContainsString('data-theme-toggle', $html);
        $this->assertStringContainsString('<h1 data-screen="demo">Demo</h1>', $html);
        $this->assertStringContainsString('src="../../assets/basecoat-theme.js"', $html);

        $full = $this->page('screens/demo/preview.html');
        $this->assertStringContainsString('<aside', $full);
        $this->assertStringContainsString('data-sidebar-toggle', $full);
    }

    public function testPageShowsExactBakeOutput(): void
    {
        $this->build();

        Configure::write('App.namespace', 'App');
        $files = (new BakeScreenCommand())->renderFiles('demo', 'Demo');
        $this->assertStringContainsString('namespace App\\Controller;', $files['src/Controller/DemoController.php']);

        $html = $this->page('screens/demo.html');
        $this->assertStringContainsString(h($files['src/Controller/DemoController.php']), $html);
        $this->assertStringContainsString(h($files['templates/Demo/index.php']), $html);
        $this->assertStringContainsString('src/Controller/DemoController.php', $html);
        $this->assertStringContainsString('templates/Demo/index.php', $html);
        $this->assertMatchesRegularExpression('/<iframe[^>]*src="\.\.\/screens\/demo\/embed\.html"/', $html);
        $this->assertMatchesRegularExpression('/<a[^>]*href="\.\.\/screens\/demo\/preview\.html"[^>]*>.*Open full page/s', $html);
        $this->assertStringContainsString('<span class="sr-only"> (opens in a new tab)</span>', $html);
        foreach (['Copy command', 'Copy controller code', 'Copy template code'] as $label) {
            $this->assertStringContainsString('<span class="sr-only">' . $label . '</span>', $html);
        }
    }

    public function testPageShowsCommand(): void
    {
        $this->build();

        $html = $this->page('screens/demo.html');
        $this->assertStringContainsString('bin/cake bake screen demo', $html);
        $this->assertStringContainsString('Demo fixture screen.', $html);
    }

    public function testScreenPageHasNoDuplicateIds(): void
    {
        $this->build(['demo', 'linked']);

        foreach (['screens/demo.html', 'screens/linked.html', 'screens/index.html', 'screens/demo/preview.html', 'screens/linked/preview.html'] as $path) {
            preg_match_all('/\sid="([^"]+)"/', $this->page($path), $matches);
            $this->assertNotEmpty($matches[1], $path);
            $duplicates = array_keys(array_filter(array_count_values($matches[1]), fn(int $count): bool => $count > 1));
            $this->assertSame([], $duplicates, $path);
        }
    }

    public function testRelativeUrls(): void
    {
        $report = $this->build(['demo', 'linked']);

        foreach ($report->pages as $page) {
            $this->assertDoesNotMatchRegularExpression('/(?:href|src|action)="\/(?!\/)/', $this->page($page), $page);
        }
    }

    public function testPreviewLinksToItsIndexStayOnThePreview(): void
    {
        $this->build(['demo', 'linked']);

        $html = $this->page('screens/linked/preview.html');
        $this->assertMatchesRegularExpression('/<form[^>]*action="preview\.html"/', $html);
        $this->assertStringContainsString('href="preview.html?page=2&amp;q=pine"', $html);
        $this->assertStringContainsString('href="../../screens/demo/preview.html"', $html, 'Sidebar links the other screens');
        $this->assertMatchesRegularExpression('#<a href="\.\./\.\./screens/demo/preview\.html"><svg[^>]*>' . preg_quote('<path d="M15 2H6', '#') . '#', $html, 'Default icon');
    }

    public function testNavigationHasScreensGroupFirst(): void
    {
        $this->build();

        $html = $this->page('components/badge.html');
        $screens = strpos($html, '>Screens<');
        $components = strpos($html, '>Data display<');
        $this->assertNotFalse($screens);
        $this->assertNotFalse($components);
        $this->assertLessThan($components, $screens);
        $this->assertStringContainsString('href="../screens/demo.html"', $html);
    }

    public function testStrictFailsOnBrokenScreen(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('`broken`');
        $this->build(['demo', 'broken']);
    }

    public function testScreenWithoutTemplateWarnsAndStrictFails(): void
    {
        $report = $this->build(['demo', 'pending']);
        $this->assertNotEmpty(preg_grep('/`pending`/', $report->warnings));
        $this->assertNotContains('screens/pending.html', $report->pages);
        $this->assertContains('screens/demo.html', $report->pages);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pending');
        $this->build(['demo', 'pending'], true);
    }
}
