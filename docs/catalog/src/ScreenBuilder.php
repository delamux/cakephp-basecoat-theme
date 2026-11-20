<?php
declare(strict_types=1);

namespace BasecoatTheme\Docs;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use Bake\Command\BakeCommand;
use Cake\Core\Configure;
use RuntimeException;

/**
 * Screens of the catalog (`bin/cake bake screen`), baked with the real command.
 *
 * CatalogBuilder renders the pages: `screens/index.html`, `screens/<slug>.html` (command,
 * preview iframe, Controller / Template tabs) and `screens/<slug>/preview.html` (the baked
 * template with its `data.php` variables inside the theme's `default` layout). This class finds
 * the screens that can be baked and bakes them into a scratch folder, so the catalog shows the
 * exact files a user gets.
 */
final class ScreenBuilder
{
    /**
     * Namespace of the baked code shown in the catalog: a default CakePHP app.
     */
    private const APP_NAMESPACE = 'App';

    /**
     * @param array<string, array{title: string, description: string, controller: string, path: string, icon?: string}> $screens
     *   Screens by slug, as in ScreenRegistry::all(); each one must be registered in ScreenRegistry.
     */
    public function __construct(private readonly array $screens)
    {
    }

    /**
     * Every registered screen.
     *
     * @return self
     */
    public static function fromRegistry(): self
    {
        return new self(ScreenRegistry::all());
    }

    /**
     * Screens that can be baked, and a warning for each one that cannot.
     *
     * @return array{0: array<string, array{title: string, description: string, controller: string, path: string, icon?: string}>, 1: list<string>}
     */
    public function collect(): array
    {
        $screens = [];
        $warnings = [];
        foreach ($this->screens as $slug => $screen) {
            if (!class_exists(BakeCommand::class)) {
                $warnings[] = sprintf('Screen `%s`: cakephp/bake is not installed, page skipped.', $slug);
                continue;
            }
            $missing = array_values(array_filter(
                ['template.twig', 'data.php'],
                fn(string $file): bool => !is_file($screen['path'] . '/' . $file),
            ));
            if ($missing) {
                $warnings[] = sprintf(
                    'Screen `%s`: no `%s` in `%s/`, page skipped.',
                    $slug,
                    implode('` or `', $missing),
                    $screen['path'],
                );
                continue;
            }
            $screens[$slug] = $screen;
        }

        return [$screens, $warnings];
    }

    /**
     * Bakes a screen with BakeScreenCommand::renderFiles() (as a default `App` app, default
     * controller name) and writes the files into `$scratchDir`.
     *
     * @param string $slug Screen slug.
     * @param string $scratchDir Folder for the baked files (created if needed).
     * @return array{command: string, controller: string, files: array<string, string>, template: string, data: array<string, mixed>}
     *   `files`: contents by path relative to the app; `template`: absolute path of the baked
     *   `index.php`; `data`: the view variables the controller sets.
     * @throws \RuntimeException When a file cannot be written.
     */
    public function bake(string $slug, string $scratchDir): array
    {
        $controller = $this->screens[$slug]['controller'];
        $namespace = Configure::read('App.namespace');
        Configure::write('App.namespace', self::APP_NAMESPACE);
        try {
            $files = (new BakeScreenCommand())->renderFiles($slug, $controller);
        } finally {
            Configure::write('App.namespace', $namespace);
        }

        $template = null;
        foreach ($files as $path => $contents) {
            $file = $scratchDir . '/' . $path;
            if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0777, true) && !is_dir(dirname($file))) {
                throw new RuntimeException(sprintf('Cannot create directory `%s`.', dirname($file)));
            }
            if (file_put_contents($file, $contents) === false) {
                throw new RuntimeException(sprintf('Cannot write `%s`.', $file));
            }
            if (str_starts_with($path, 'templates/')) {
                $template = $file;
            }
        }
        if ($template === null) {
            throw new RuntimeException(sprintf('Screen `%s`: bake produced no template.', $slug));
        }

        return [
            'command' => 'bin/cake bake screen ' . $slug,
            'controller' => basename(dirname($template)),
            'files' => $files,
            'template' => $template,
            'data' => ScreenRegistry::data($slug),
        ];
    }
}
