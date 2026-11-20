<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every built-in screen baked with `--plugin` uses the plugin domain for all its strings, and the
 * generated template has no user-facing English literal outside `__d()` / `__dn()`.
 *
 * Pragmatic checks on the generated source (not a parser): HTML text nodes, the usual text options
 * of the helpers (`label`, `description`, `placeholder`…), the first argument of the text helpers
 * (`render`, `link`, `submit`) and `h('…')`.
 */
class ScreenTranslationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
    }

    protected function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function screens(): array
    {
        $slugs = array_keys(ScreenRegistry::all());

        return array_combine($slugs, array_map(fn(string $slug): array => [$slug], $slugs));
    }

    private function bake(string $slug): string
    {
        $files = (new BakeScreenCommand())->renderFiles($slug, 'Demo', 'Demo');

        return $files['templates/Demo/index.php'];
    }

    #[DataProvider('screens')]
    public function testStringsUseThePluginDomain(string $slug): void
    {
        $code = $this->bake($slug);

        $this->assertStringContainsString("__d('Demo', ", $code);
        $this->assertDoesNotMatchRegularExpression('/(?<![\w>])__n?\(/', $code, 'No app-domain __() / __n() under --plugin');
    }

    #[DataProvider('screens')]
    public function testNoUntranslatedLiterals(string $slug): void
    {
        $code = $this->bake($slug);

        // HTML text nodes outside PHP blocks.
        $html = (string)preg_replace(['/<\?(php|=).*?\?>/s', '/&#?\w+;/'], '', $code);
        preg_match_all('/>\s*([^<>]*[A-Za-z]{2,}[^<>]*?)\s*</', $html, $text);
        $this->assertSame([], $text[1], 'Text nodes must be wrapped in __d()');

        // Helper options and arguments with a literal English string.
        $patterns = [
            '/\'(label|description|placeholder|empty|tab|title|text|aria-label|alt)\'\s*=>\s*\'[A-Za-z][^\']*\'/',
            '/->(render|link|submit|button)\(\s*\'[A-Z][^\']*\'/',
            '/->icon\(\s*\'[a-z0-9-]+\',\s*\'[A-Za-z][^\']*\'/',
            '/\bh\(\s*\'[A-Za-z][^\']*\'/',
            '/assign\(\'title\',\s*\'/',
        ];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $code, $found);
            $this->assertSame([], $found[0], $pattern);
        }
    }
}
