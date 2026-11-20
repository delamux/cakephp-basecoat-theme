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

    private const BADGE = [
        'badge' => ['alias' => 'Badge', 'title' => 'Badge', 'group' => 'Data display', 'intro' => 'Small status labels.'],
    ];

    private string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
        $this->out = sys_get_temp_dir() . '/basecoat-catalog-test-' . bin2hex(random_bytes(4));
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

    private function build(array $registry = self::BADGE, string $examples = 'examples', bool $strict = false)
    {
        $builder = new CatalogBuilder(self::FIXTURES . $examples, self::FIXTURES . 'pages', $registry);

        return $builder->build($this->out, $strict);
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

    public function testIndexPageRendered(): void
    {
        $report = $this->build();

        $this->assertContains('index.html', $report->pages);
        $html = (string)file_get_contents($this->out . '/index.html');
        $this->assertMatchesRegularExpression('/<aside[^>]*class="[^"]*\bsidebar\b/', $html);
        $this->assertStringContainsString('Browse components', $html);
    }
}
