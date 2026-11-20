<?php
declare(strict_types=1);

namespace BasecoatTheme\Docs;

use InvalidArgumentException;

/**
 * A catalog example: `examples/<helper-slug>/<NN>-<example-slug>.php`.
 *
 * The file starts with a docblock holding `@title` (required) and `@description`. Code above a line
 * that is exactly `// ---` is setup (shown collapsed); the rest is the code shown in the PHP tab.
 */
final class ExampleFile
{
    private const SETUP_MARKER = '// ---';

    /**
     * @param string $path Absolute path of the example file.
     * @param string $slug Helper slug (folder name).
     * @param int $order Position, from the `NN-` file name prefix.
     * @param string $title Example title.
     * @param string $description One sentence description.
     * @param string $setup Setup code (above `// ---`), or ''.
     * @param string $code Displayed code.
     */
    private function __construct(
        public readonly string $path,
        public readonly string $slug,
        public readonly int $order,
        public readonly string $title,
        public readonly string $description,
        public readonly string $setup,
        public readonly string $code,
    ) {
    }

    /**
     * Parses an example file.
     *
     * @param string $path Example file path.
     * @return self
     * @throws \InvalidArgumentException When the file cannot be read, has no `NN-` prefix or no `@title`.
     */
    public static function fromPath(string $path): self
    {
        $source = is_file($path) ? file_get_contents($path) : false;
        if ($source === false) {
            throw new InvalidArgumentException(sprintf('Cannot read example file `%s`.', $path));
        }
        if (!preg_match('/^(\d+)-[^\/]+\.php$/', basename($path), $name)) {
            throw new InvalidArgumentException(sprintf('Example file `%s` must be named `NN-<slug>.php`.', $path));
        }

        $source = str_replace("\r\n", "\n", $source);
        $source = (string)preg_replace('/^<\?php\s*/', '', $source);
        $header = '';
        if (preg_match('/^\/\*\*.*?\*\//s', $source, $docblock)) {
            $header = $docblock[0];
            $source = substr($source, strlen($header));
        }

        $title = self::tag($header, 'title');
        if ($title === '') {
            throw new InvalidArgumentException(sprintf('Example file `%s` has no `@title` in its header docblock.', $path));
        }

        $setup = '';
        $code = $source;
        $lines = explode("\n", $source);
        $marker = array_search(self::SETUP_MARKER, array_map('rtrim', $lines), true);
        if ($marker !== false) {
            $setup = implode("\n", array_slice($lines, 0, $marker));
            $code = implode("\n", array_slice($lines, $marker + 1));
        }

        return new self(
            $path,
            basename(dirname($path)),
            (int)$name[1],
            $title,
            self::tag($header, 'description'),
            trim($setup),
            trim($code),
        );
    }

    /**
     * Value of a single-line docblock tag.
     *
     * @param string $docblock Docblock.
     * @param string $tag Tag name without `@`.
     * @return string
     */
    private static function tag(string $docblock, string $tag): string
    {
        if (!preg_match('/^\s*\*\s*@' . $tag . '\s+(.+)$/m', $docblock, $match)) {
            return '';
        }

        return trim($match[1]);
    }
}
