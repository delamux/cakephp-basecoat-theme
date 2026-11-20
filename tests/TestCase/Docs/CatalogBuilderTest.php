<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Docs;

use BasecoatTheme\Docs\CatalogBuilder;
use Cake\TestSuite\TestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Static catalog generation from example files.
 */
class CatalogBuilderTest extends TestCase
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
        $this->out = self::REPO . '/build/catalog-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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

    private function build(array $registry = self::BADGE, string $examples = 'examples', bool $strict = false, string $pages = 'pages', ?string $out = null)
    {
        $builder = new CatalogBuilder(self::FIXTURES . $examples, self::FIXTURES . $pages, $registry);

        return $builder->build($out ?? $this->out, $strict);
    }

    public function testBuildsHelperPageWithCodeAndOutput(): void
    {
        $report = $this->build();

        $this->assertContains('components/badge.html', $report->pages);
        $html = (string)file_get_contents($this->out . '/components/badge.html');
        $this->assertStringContainsString('Sizes', $html);
        $this->assertStringContainsString('$this-&gt;Badge-&gt;render', $html);
        $this->assertStringContainsString('<span class="badge">A</span>', $html);
        $this->assertStringContainsString('Small status labels.', $html);
        $this->assertFileExists($this->out . '/assets/basecoat-theme.js');
        $this->assertFileExists($this->out . '/assets/catalog.js');
        $this->assertFileExists($this->out . '/components/index.html');
        // Plugin stylesheets at their plugin asset URL (`BasecoatTheme.workflow` => /basecoat_theme/css/workflow.css).
        $this->assertFileEquals(ROOT . '/webroot/css/workflow.css', $this->out . '/basecoat_theme/css/workflow.css');
    }

    public function testSetupIsCollapsed(): void
    {
        $this->build();

        $html = (string)file_get_contents($this->out . '/components/badge.html');
        $this->assertMatchesRegularExpression('#<details[^>]*>\s*<summary[^>]*>Setup</summary>.*\$rows = \[1\];#s', $html);
    }

    public function testWarningFailsBuild(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('broken/01-warn.php');
        $this->build(['broken' => ['alias' => 'Badge', 'title' => 'Broken', 'group' => 'Feedback', 'intro' => '']], 'broken');
    }

    public function testUtf8Preserved(): void
    {
        $this->build();

        $this->assertStringContainsString('Café — 12 €', (string)file_get_contents($this->out . '/components/badge.html'));
    }

    public function testOutputUsesRelativeUrls(): void
    {
        $report = $this->build();

        $this->assertNotEmpty($report->pages);
        foreach ($report->pages as $page) {
            $html = (string)file_get_contents($this->out . '/' . $page);
            $this->assertDoesNotMatchRegularExpression('/(?:href|src)="\/(?!\/)/', $html, $page);
        }
    }

    public function testStrictFailsOnHelperWithoutExamples(): void
    {
        $registry = self::BADGE + [
            'kbd' => ['alias' => 'Kbd', 'title' => 'Kbd', 'group' => 'Data display', 'intro' => 'Keys.'],
        ];

        $report = $this->build($registry);
        $this->assertNotEmpty(preg_grep('/\bkbd\b/', $report->warnings));
        $this->assertNotContains('components/kbd.html', $report->pages);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('kbd');
        $this->build($registry, 'examples', true);
    }

    public function testStrictFailsOnMissingHelperClass(): void
    {
        $registry = self::BADGE + [
            'not-built-yet' => ['alias' => 'NotBuiltYet', 'title' => 'Not built yet', 'group' => 'Layout', 'intro' => ''],
        ];

        $report = $this->build($registry);
        $this->assertNotEmpty(preg_grep('/NotBuiltYet/', $report->warnings));
        $this->assertContains('components/badge.html', $report->pages);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NotBuiltYet');
        $this->build($registry, 'examples', true);
    }

    public function testAutoIdsUniqueAcrossExamplesOfAPage(): void
    {
        $this->build(['tabs' => ['alias' => 'Tabs', 'title' => 'Tabs', 'group' => 'Layout', 'intro' => '']], 'ids');

        $html = (string)file_get_contents($this->out . '/components/tabs.html');
        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $this->assertContains('tabs-1', $matches[1]);
        $this->assertContains('tabs-2', $matches[1]);
        $duplicates = array_keys(array_filter(array_count_values($matches[1]), fn(int $count): bool => $count > 1));
        $this->assertSame([], $duplicates);
    }

    public function testIndexPageRendered(): void
    {
        $report = $this->build();

        $this->assertContains('index.html', $report->pages);
        $html = (string)file_get_contents($this->out . '/index.html');
        $this->assertMatchesRegularExpression('/<aside[^>]*class="[^"]*\bsidebar\b/', $html);
        $this->assertStringContainsString('Browse components', $html);
    }

    public function testCodeOnlyExampleHasNoLivePreview(): void
    {
        $this->build(self::BADGE, 'codeonly');

        $html = (string)file_get_contents($this->out . '/components/badge.html');
        $this->assertStringContainsString('Page assets', $html);
        $this->assertStringNotContainsString('<script src="https://cdn.example.com/page-asset.js"', $html, 'Not injected live');
        $this->assertStringContainsString('&lt;script src=&quot;https://cdn.example.com/page-asset.js&quot;', $html, 'HTML tab');
        $this->assertStringContainsString('echo &#039;&lt;script', $html, 'PHP tab');
        $this->assertStringNotContainsString('catalog-preview', $html);
    }

    public function testHomePageNoticeFailsBuild(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('broken-pages/index.php');
        $this->build(self::BADGE, 'examples', false, 'broken-pages');
    }

    public function testOutputDirectoryIsClearedBeforeBuilding(): void
    {
        mkdir($this->out . '/components', 0777, true);
        file_put_contents($this->out . '/components/removed-helper.html', 'stale');

        $this->build();

        $this->assertFileDoesNotExist($this->out . '/components/removed-helper.html');
        $this->assertFileExists($this->out . '/components/badge.html');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unsafeOutputDirs(): array
    {
        return [
            'repository root' => [''],
            'src' => ['/src'],
            'docs' => ['/docs'],
            '.git' => ['/.git'],
            'vendor' => ['/vendor'],
            'tests' => ['/tests'],
            'build itself' => ['/build'],
            'build itself, trailing slash' => ['/build/'],
            'build/. (build itself)' => ['/build/.'],
            'build/../src' => ['/build/../src'],
            'build/x/../../src' => ['/build/x/../../src'],
            'outside the repository' => ['/../elsewhere'],
            'build/../../elsewhere' => ['/build/../../elsewhere'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsafeOutputDirs')]
    public function testUnsafeOutputDirIsRefused(string $suffix): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing');
        CatalogBuilder::safeOutputDir(self::REPO . $suffix);
    }

    public function testOutputDirInsideBuildIsAccepted(): void
    {
        $this->assertSame(realpath(self::REPO) . '/build/gh-pages', CatalogBuilder::safeOutputDir(self::REPO . '/build/gh-pages'));
        $this->assertSame(realpath(self::REPO) . '/build/x/y', CatalogBuilder::safeOutputDir(self::REPO . '/build/x/y/'));
    }

    public function testSymlinkOutputDirIsRefused(): void
    {
        $link = self::REPO . '/build/link-' . bin2hex(random_bytes(4));
        symlink(realpath(self::REPO . '/src'), $link);
        try {
            CatalogBuilder::safeOutputDir($link);
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Refusing', $e->getMessage());
        } finally {
            unlink($link);
        }
        $this->assertFileExists(self::REPO . '/src/View/Helper/ButtonHelper.php');
    }

    public function testRefusedBuildDeletesAndWritesNothing(): void
    {
        foreach (['/src', '/build/../docs', '/.git', ''] as $suffix) {
            try {
                $this->build(self::BADGE, 'examples', false, 'pages', self::REPO . $suffix);
                $this->fail('Expected a RuntimeException for ' . $suffix);
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('Refusing', $e->getMessage());
            }
        }
        $this->assertFileExists(self::REPO . '/src/View/Helper/ButtonHelper.php');
        $this->assertFileExists(self::REPO . '/docs/catalog/helpers.php');
        $this->assertFileExists(self::REPO . '/.git/HEAD');
        $this->assertFileDoesNotExist(self::REPO . '/index.html', 'Nothing written to the repository root');
        $this->assertFileDoesNotExist(self::REPO . '/components/badge.html');
        $this->assertFileDoesNotExist(self::REPO . '/src/components/badge.html');
    }

    public function testRefusesToClearOutsideRepository(): void
    {
        $outside = sys_get_temp_dir() . '/basecoat-catalog-outside-' . bin2hex(random_bytes(4));
        mkdir($outside);
        file_put_contents($outside . '/keep.txt', 'keep');
        try {
            $this->build(self::BADGE, 'examples', false, 'pages', $outside);
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Refusing', $e->getMessage());
            $this->assertFileExists($outside . '/keep.txt');
        } finally {
            @unlink($outside . '/keep.txt');
            @rmdir($outside);
        }
    }

    public function testCdnAssetsHaveSubresourceIntegrity(): void
    {
        $this->build();

        foreach (['index.html', 'components/badge.html', 'components/index.html'] as $page) {
            $html = (string)file_get_contents($this->out . '/' . $page);
            preg_match_all('#<(?:script|link)\b[^>]*https://cdnjs\.cloudflare\.com/[^>]*>#', $html, $tags);
            $this->assertCount(5, $tags[0], $page);
            foreach ($tags[0] as $tag) {
                $this->assertMatchesRegularExpression('#\sintegrity="sha384-[A-Za-z0-9+/]{64}"#', $tag, $tag);
                $this->assertStringContainsString(' crossorigin="anonymous"', $tag);
            }
        }
    }
}
