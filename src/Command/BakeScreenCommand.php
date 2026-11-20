<?php
declare(strict_types=1);

namespace BasecoatTheme\Command;

use BasecoatTheme\Bake\PhpLiteral;
use BasecoatTheme\Bake\ScreenRegistry;
use Bake\Command\BakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\TwigView\View\TwigView;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * `bin/cake bake screen <screen> [<controller>]`: bakes a ready-made screen (see ScreenRegistry)
 * as a controller whose `index()` sets sample data and an `index` template built with the theme
 * helpers. Registered by BasecoatThemePlugin::console() only when cakephp/bake is installed.
 *
 * Templates resolve from this plugin (`BasecoatTheme.Screen/controller` and the screen's own
 * `template.twig`), so `--theme` is not needed.
 */
class BakeScreenCommand extends BakeCommand
{
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Bake a ready-made BasecoatTheme screen (controller with sample data + index template).';
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->extractCommonProperties($args);

        if ($args->getOption('list')) {
            $this->listScreens($io);

            return static::CODE_SUCCESS;
        }

        $slug = $args->getArgument('screen');
        if ($slug === null || $slug === '') {
            $io->error('Missing the screen to bake. Run `bin/cake bake screen --list` to see the screens.');

            return static::CODE_ERROR;
        }

        try {
            $screen = ScreenRegistry::get($slug);
            $files = $this->renderFiles($slug, $args->getArgument('controller') ?? $screen['controller'], $this->plugin);
        } catch (InvalidArgumentException | UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return static::CODE_ERROR;
        }

        $root = null;
        if ($this->plugin) {
            try {
                $root = Plugin::path($this->plugin);
            } catch (MissingPluginException) {
                $io->error(sprintf(
                    'Plugin `%s` is not loaded. Create it with `bin/cake bake plugin %s` or load it with `bin/cake plugin load %s`.',
                    $this->plugin,
                    $this->plugin,
                    $this->plugin,
                ));

                return static::CODE_ERROR;
            }
        }
        foreach ($files as $relative => $contents) {
            $this->writeFile($io, $this->outputPath($relative, $root), $contents, $this->force);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Renders the files of a screen without writing anything.
     *
     * @param string $slug Screen slug (ScreenRegistry).
     * @param string $controller Controller name, camelized (`inbox`, `team_inbox` => `Inbox`, `TeamInbox`).
     * @param string|null $plugin Plugin to bake into (`Vendor/Name` allowed), or null for the app.
     * @return array<string, string> Contents by path relative to the app (or plugin) root:
     *   `src/Controller/<Controller>Controller.php` and `templates/<Controller>/index.php`.
     * @throws \InvalidArgumentException For an unknown screen or an invalid controller name.
     * @throws \UnexpectedValueException When the screen `data.php` is missing or not an array.
     */
    public function renderFiles(string $slug, string $controller, ?string $plugin = null): array
    {
        $screen = ScreenRegistry::get($slug);
        $name = $this->_camelize($controller);
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid controller name `%s`: use a name like `Inbox` or `team_inbox`.',
                $controller,
            ));
        }

        $vars = [];
        foreach (ScreenRegistry::data($slug) as $var => $value) {
            if (!is_string($var) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $var)) {
                throw new InvalidArgumentException(sprintf(
                    'Screen `%s`: `%s` is not a valid view variable name.',
                    $slug,
                    $var,
                ));
            }
            $vars[$var] = PhpLiteral::export($value, 2);
        }

        $context = [
            'namespace' => $plugin ? $this->_pluginNamespace($plugin) : (string)Configure::read('App.namespace'),
            'name' => $name,
            'plugin' => $plugin,
            // As in bake's CRUD templates: strings use `__d('<plugin>', ...)` when baking into a plugin.
            'useDomain' => (bool)$plugin,
            'slug' => $slug,
            'title' => $screen['title'],
            'description' => $screen['description'],
            'vars' => $vars,
        ];

        $renderer = $this->createTemplateRenderer();
        $renderer->set($context);
        $view = $renderer->getView();
        assert($view instanceof TwigView);
        // No layout: `Bake.default` only echoes the content and needs the Bake plugin loaded.
        $controllerCode = $view->render('BasecoatTheme.Screen/controller', false);

        $templateFile = $screen['path'] . DIRECTORY_SEPARATOR . 'template.twig';
        $source = is_file($templateFile) ? file_get_contents($templateFile) : false;
        if ($source === false) {
            throw new InvalidArgumentException(sprintf('Screen `%s` has no `%s`.', $slug, $templateFile));
        }
        $helpers = iterator_to_array($view->helpers()->getIterator());
        $templateCode = $view->getTwig()
            ->createTemplate($source, $templateFile)
            ->render($context + $helpers);

        return [
            "src/Controller/{$name}Controller.php" => $controllerCode,
            "templates/{$name}/index.php" => $templateCode,
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = $this->_setCommonOptions($parser);
        $parser->setDescription(static::getDescription())
            ->addArgument('screen', [
                'help' => 'The screen to bake (see --list).',
                'required' => false,
            ])
            ->addArgument('controller', [
                'help' => 'Controller name (default: the screen\'s, e.g. Users).',
                'required' => false,
            ])
            ->addOption('list', [
                'boolean' => true,
                'help' => 'List the available screens.',
            ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return void
     */
    protected function listScreens(ConsoleIo $io): void
    {
        $screens = ScreenRegistry::all();
        $width = max(array_map('strlen', array_keys($screens)));
        $io->out('Available screens:');
        foreach ($screens as $slug => $screen) {
            $io->out(sprintf(
                '  <info>%s</info>  %s: %s',
                str_pad($slug, $width),
                $screen['title'],
                $screen['description'],
            ));
        }
        $io->out('');
        $io->out('Bake one with `bin/cake bake screen <screen> [<controller>]`.');
    }

    /**
     * Absolute path of a file of renderFiles(): under the plugin root, or where bake writes app
     * code (`APP` for `src/`, the first `App.paths.templates` entry for `templates/`).
     *
     * @param string $relative Path relative to the app or plugin root.
     * @param string|null $pluginRoot Plugin root, or null for the app.
     * @return string
     */
    protected function outputPath(string $relative, ?string $pluginRoot): string
    {
        if ($pluginRoot !== null) {
            $path = $pluginRoot . $relative;
        } elseif (str_starts_with($relative, 'src/')) {
            $path = APP . substr($relative, 4);
        } else {
            $templates = (array)Configure::read('App.paths.templates');
            $path = rtrim((string)($templates[0] ?? ROOT . '/templates/'), '/\\') . '/' . substr($relative, 10);
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
