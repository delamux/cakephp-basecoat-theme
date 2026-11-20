<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Docs;

use BasecoatTheme\Docs\ExampleFile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Parsing of catalog example files.
 */
class ExampleFileTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/fixtures/';

    public function testParsesHeaderAndCode(): void
    {
        $example = ExampleFile::fromPath(self::FIXTURES . 'examples/badge/02-sizes.php');

        $this->assertSame(self::FIXTURES . 'examples/badge/02-sizes.php', $example->path);
        $this->assertSame('Sizes', $example->title);
        $this->assertSame('Small and default.', $example->description);
        $this->assertSame(2, $example->order);
        $this->assertSame('badge', $example->slug);
        $this->assertSame('', $example->setup);
        $this->assertSame("echo \$this->Badge->render('A');", $example->code);
        $this->assertTrue($example->preview, 'Live preview by default');
    }

    public function testPreviewFalse(): void
    {
        $example = ExampleFile::fromPath(self::FIXTURES . 'codeonly/badge/01-assets.php');

        $this->assertFalse($example->preview);
        $this->assertSame('Page assets', $example->title);
    }

    public function testSplitsSetup(): void
    {
        $example = ExampleFile::fromPath(self::FIXTURES . 'examples/badge/03-setup.php');

        $this->assertSame('$rows = [1];', $example->setup);
        $this->assertSame('echo count($rows);', $example->code);
    }

    public function testMissingTitleThrows(): void
    {
        $path = self::FIXTURES . 'invalid/badge/01-no-title.php';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($path);
        ExampleFile::fromPath($path);
    }
}
