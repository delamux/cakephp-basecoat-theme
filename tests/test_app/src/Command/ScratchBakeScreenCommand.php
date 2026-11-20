<?php
declare(strict_types=1);

namespace TestApp\Command;

use BasecoatTheme\Command\BakeScreenCommand;

/**
 * `bake screen` for the tests: app `src/` output goes to a scratch dir instead of `APP` (the test
 * app sources), so a crashed or parallel run never leaves generated controllers in the repo.
 * Registered as `bake screen` by TestApp\Application::pluginConsole().
 */
class ScratchBakeScreenCommand extends BakeScreenCommand
{
    /**
     * Directory that replaces `APP` (set by the test; defaults to a dir under TMP).
     */
    public static ?string $srcRoot = null;

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'bake screen';
    }

    /**
     * @inheritDoc
     */
    protected function outputPath(string $relative, ?string $pluginRoot): string
    {
        if ($pluginRoot === null && str_starts_with($relative, 'src/')) {
            $root = static::$srcRoot ?? TMP . 'bake_screen_src' . DIRECTORY_SEPARATOR;

            return str_replace('/', DIRECTORY_SEPARATOR, $root . substr($relative, 4));
        }

        return parent::outputPath($relative, $pluginRoot);
    }
}
