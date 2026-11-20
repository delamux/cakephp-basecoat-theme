<?php
declare(strict_types=1);

namespace BasecoatTheme\Docs;

use BasecoatTheme\View\BasecoatHelpers;
use BasecoatTheme\View\Helper\AbstractComponentHelper;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\View;
use Closure;
use ErrorException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

/**
 * Builds the static component catalog from the example files and the real helpers.
 *
 * Output (all links relative, so the site works under any sub-path):
 * - `index.html`: `pages/index.php` rendered with the theme's `default` layout;
 * - `components/index.html`: helpers grouped as in the registry;
 * - `components/<slug>.html`: one page per helper with its examples (preview, PHP, HTML);
 * - `screens/index.html`, `screens/<slug>.html` and `screens/<slug>/preview.html`: the bake
 *   screens (see ScreenBuilder);
 * - `assets/`: catalog CSS/JS and the plugin's `basecoat-theme.js`.
 *
 * Every example and screen runs in a fresh View; any PHP warning, notice, deprecation or exception
 * fails the build.
 */
final class CatalogBuilder
{
    private const HELPER_NAMESPACE = 'BasecoatTheme\\View\\Helper\\';

    private const TEMPLATES = __DIR__ . '/../templates/';

    private const ASSETS = __DIR__ . '/../assets/';

    private const PLUGIN_JS = __DIR__ . '/../../../webroot/js/basecoat-theme.js';

    private const REPO_ROOT = __DIR__ . '/../../..';

    /**
     * @param string $examplesDir Directory with one folder of examples per helper slug.
     * @param string $pagesDir Directory with `index.php` (catalog home).
     * @param array<string, array{alias: string, title: string, group: string, intro: string}> $registry Helpers by slug.
     * @param \BasecoatTheme\Docs\ScreenBuilder $screens Bake screens (none by default).
     */
    public function __construct(
        private readonly string $examplesDir,
        private readonly string $pagesDir,
        private readonly array $registry,
        private readonly ScreenBuilder $screens = new ScreenBuilder([]),
    ) {
    }

    /**
     * Builds the site.
     *
     * The output directory must be a folder inside the repository's `build/` (see safeOutputDir());
     * it is emptied first, so files of removed helpers do not linger.
     *
     * @param string $outDir Output directory, e.g. `<repo>/build/gh-pages`.
     * @param bool $strict Fail on warnings (helper without examples, helper class missing, screen
     *   without `template.twig` / `data.php`).
     * @return \BasecoatTheme\Docs\BuildReport
     * @throws \RuntimeException When the output directory is refused, an example or the home page
     *   fails, or on any warning in strict mode. Nothing is deleted or written before the checks pass.
     */
    public function build(string $outDir, bool $strict = false): BuildReport
    {
        $this->setUpRouter();
        [$helpers, $warnings] = $this->collect();
        [$screens, $screenWarnings] = $this->screens->collect();
        $warnings = [...$warnings, ...$screenWarnings];
        if ($strict && $warnings) {
            throw new RuntimeException("Strict mode, " . count($warnings) . " problem(s):\n- " . implode("\n- ", $warnings));
        }

        $outDir = $this->clearOutputDir($outDir);
        $this->copyAssets($outDir);
        $menu = fn(string $path): array => $this->menu($helpers, $screens, $path);
        $pages = $this->buildScreens($outDir, $screens, $menu);

        foreach ($helpers as $slug => $helper) {
            $path = 'components/' . $slug . '.html';
            $blocks = '';
            $charts = false;
            $idCounters = [];
            foreach ($helper['examples'] as $example) {
                $preview = $this->runExample($example, $idCounters);
                $charts = $charts || ($example->preview && str_contains($preview, 'data-basecoat-chart'));
                $blocks .= $this->renderTemplate($this->view(), 'example', [
                    'example' => $example,
                    'id' => 'example-' . $slug . '-' . $example->order,
                    'preview' => $preview,
                    'html' => HtmlFormatter::pretty($preview),
                ]);
            }
            $content = $this->renderTemplate($this->view(), 'helper_page', [
                'entry' => $helper['entry'],
                'examples' => $blocks,
            ]);
            $this->write($outDir, $path, $this->renderLayout($path, $helper['entry']['title'], $content, $menu($path), $charts));
            $pages[] = $path;
        }

        $path = 'components/index.html';
        $content = $this->renderTemplate($this->view(), 'components_index', ['groups' => $this->groups($helpers)]);
        $this->write($outDir, $path, $this->renderLayout($path, 'Components', $content, $menu($path), false));
        $pages[] = $path;

        $this->write($outDir, 'index.html', $this->renderHome($menu('index.html')));
        array_unshift($pages, 'index.html');

        return new BuildReport($pages, $warnings);
    }

    /**
     * Screens section: index, one page per screen and its full-page preview.
     *
     * Each screen is baked with the real command into a scratch folder (`<outDir>/.bake`, removed
     * afterwards); the page shows the baked files byte for byte and the preview renders the baked
     * template with the screen's `data.php` variables.
     *
     * @param string $outDir Output directory.
     * @param array<string, array{title: string, description: string, controller: string, path: string, icon?: string}> $screens Screens with a page.
     * @param \Closure(string): array<array<string, mixed>> $menu Sidebar menu for a page path.
     * @return list<string> Pages written.
     * @throws \RuntimeException When baking or rendering a screen fails; the message names the screen.
     */
    private function buildScreens(string $outDir, array $screens, Closure $menu): array
    {
        $scratch = $outDir . '/.bake';
        $pages = [];
        try {
            foreach ($screens as $slug => $screen) {
                $baked = $this->strict(
                    sprintf('Screen `%s` (bake)', $slug),
                    fn(): array => $this->screens->bake($slug, $scratch . '/' . $slug),
                );

                $path = 'screens/' . $slug . '/preview.html';
                $this->write($outDir, $path, $this->renderScreenPreview($slug, $screens, $baked));
                $pages[] = $path;

                $path = 'screens/' . $slug . '.html';
                $content = $this->renderTemplate($this->view(), 'screen_page', [
                    'slug' => $slug,
                    'screen' => $screen,
                    'baked' => $baked,
                ]);
                $this->write($outDir, $path, $this->renderLayout($path, $screen['title'] . ' screen', $content, $menu($path), false));
                $pages[] = $path;
            }
        } finally {
            if (is_dir($scratch)) {
                $this->emptyDir($scratch);
                rmdir($scratch);
            }
        }

        $path = 'screens/index.html';
        $content = $this->renderTemplate($this->view(), 'screens_index', ['screens' => $screens]);
        $this->write($outDir, $path, $this->renderLayout($path, 'Screens', $content, $menu($path), false));
        array_unshift($pages, $path);

        return $pages;
    }

    /**
     * Full-page preview of a baked screen: its template, with the `data.php` variables the baked
     * controller sets, inside the theme's `default` layout of an invented app (sidebar linking
     * the other previews).
     *
     * The request is the baked controller's `index`, so links and GET forms to `index` (search,
     * filters, `?page=`) stay on the preview.
     *
     * @param string $slug Screen slug.
     * @param array<string, array{title: string, description: string, controller: string, path: string, icon?: string}> $screens Screens with a page.
     * @param array{command: string, controller: string, files: array<string, string>, template: string, data: array<string, mixed>} $baked ScreenBuilder::bake() result.
     * @return string
     * @throws \RuntimeException When the template throws or emits any PHP error; the message names the screen.
     */
    private function renderScreenPreview(string $slug, array $screens, array $baked): string
    {
        $view = $this->view($baked['controller']);
        $view->setTheme('BasecoatTheme');
        $view->setLayout('default');
        $view->set($baked['data']);
        $view->set([
            'brand' => ['name' => 'Fernway', 'url' => '#', 'icon' => 'chart-column'],
            'sidebarMenu' => $this->previewMenu($slug, $screens),
            'sidebarUser' => ['name' => 'Elena Sarto', 'email' => 'elena.sarto@example.com'],
        ]);
        $view->append('meta', '<link rel="icon" href="data:,">');

        $html = $this->strict(sprintf('Screen `%s` (template `%s`)', $slug, $baked['template']), function () use ($view, $baked): string {
            $content = $this->evaluate($view, $baked['template'], $baked['data']);

            return $view->renderLayout($content);
        });

        // `/team-inbox`, `/team-inbox/index`, with or without a query string => this preview.
        $index = preg_quote('/' . Inflector::dasherize($baked['controller']), '#');
        $html = (string)preg_replace('#(?<=\s)(href|action)="' . $index . '(?:/index)?(?=["?])#', '$1="preview.html', $html);

        return $this->relativize($html, 2);
    }

    /**
     * Sidebar of the screen previews: the invented app's pages, one per screen.
     *
     * @param string $current Slug of the previewed screen.
     * @param array<string, array{title: string, description: string, controller: string, path: string, icon?: string}> $screens Screens with a page.
     * @return array<array<string, mixed>>
     */
    private function previewMenu(string $current, array $screens): array
    {
        $items = [['label' => 'Dashboard', 'url' => '#', 'icon' => 'house', 'current' => false]];
        foreach ($screens as $slug => $screen) {
            $items[] = [
                'label' => $screen['title'],
                'url' => '/screens/' . $slug . '/preview.html',
                'icon' => $screen['icon'] ?? 'file-text',
                'current' => $slug === $current,
            ];
        }
        $items[] = ['label' => 'Settings', 'url' => '#', 'icon' => 'settings-2', 'current' => false];

        return [['type' => 'group', 'label' => 'Workspace', 'items' => $items]];
    }

    /**
     * Helpers that get a page (class exists, at least one example) and the warnings for the others.
     *
     * @return array{0: array<string, array{entry: array<string, string>, examples: list<\BasecoatTheme\Docs\ExampleFile>}>, 1: list<string>}
     */
    private function collect(): array
    {
        $helpers = [];
        $warnings = [];
        foreach ($this->registry as $slug => $entry) {
            $class = self::HELPER_NAMESPACE . $entry['alias'] . 'Helper';
            if (!class_exists($class)) {
                $warnings[] = sprintf('Helper `%s` (%s): class `%s` not found, page skipped.', $entry['alias'], $slug, $class);
                continue;
            }
            $examples = array_map(
                fn(string $file): ExampleFile => ExampleFile::fromPath($file),
                glob($this->examplesDir . '/' . $slug . '/*.php') ?: [],
            );
            if (!$examples) {
                $warnings[] = sprintf('Helper `%s` (%s): no examples in `%s/%s/`, page skipped.', $entry['alias'], $slug, $this->examplesDir, $slug);
                continue;
            }
            usort($examples, fn(ExampleFile $a, ExampleFile $b): int => [$a->order, $a->path] <=> [$b->order, $b->path]);
            $helpers[$slug] = ['entry' => $entry, 'examples' => $examples];
        }
        foreach (glob($this->examplesDir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (!isset($this->registry[basename($dir)])) {
                $warnings[] = sprintf('Examples folder `%s` does not match any helper in the registry.', $dir);
            }
        }

        return [$helpers, $warnings];
    }

    /**
     * Runs an example in a fresh View and returns its output.
     *
     * The helpers' auto-id counters carry over between the examples of a page (`$idCounters`),
     * so two examples never render the same generated DOM id.
     *
     * @param \BasecoatTheme\Docs\ExampleFile $example Example.
     * @param array<string, array<string, int>> $idCounters Id counters by helper alias (read and updated).
     * @return string
     * @throws \RuntimeException When the example throws or emits any PHP error.
     */
    private function runExample(ExampleFile $example, array &$idCounters): string
    {
        $view = $this->view();
        $this->seedIdCounters($view, $idCounters);
        $html = $this->evaluateStrict($view, $example->path, sprintf('Example `%s`', $example->path));
        $idCounters = $this->readIdCounters($view);

        return $html;
    }

    /**
     * Evaluates a file in a View; any PHP warning, notice or deprecation becomes an exception.
     *
     * Used for the examples, the catalog home page and the screen previews.
     *
     * @param \Cake\View\View $view View.
     * @param string $file File to evaluate.
     * @param string $what What the file is, for the error message (e.g. ``Example `<path>` ``).
     * @param array<string, mixed> $vars Variables of the file.
     * @return string
     * @throws \RuntimeException When the file throws or emits any PHP error; the message starts with `$what`.
     */
    private function evaluateStrict(View $view, string $file, string $what, array $vars = []): string
    {
        return $this->strict($what, fn(): string => $this->evaluate($view, $file, $vars));
    }

    /**
     * Runs `$callback`; any PHP warning, notice or deprecation becomes an exception.
     *
     * @template T
     * @param string $what What runs, for the error message.
     * @param \Closure(): T $callback Callback.
     * @return T
     * @throws \RuntimeException When the callback throws or emits any PHP error; output buffers it
     *   opened are discarded.
     */
    private function strict(string $what, Closure $callback): mixed
    {
        set_error_handler(function (int $severity, string $message, string $errorFile, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $errorFile, $line);
        });
        $level = ob_get_level();
        try {
            return $callback();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw new RuntimeException(sprintf(
                '%s failed: %s (%s:%d)',
                $what,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ), 0, $e);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Auto-id counters of the component helpers loaded in a View, by alias.
     *
     * @param \Cake\View\View $view View.
     * @return array<string, array<string, int>>
     */
    private function readIdCounters(View $view): array
    {
        $read = fn(): array => $this->idCounters;
        $counters = [];
        foreach ($view->helpers()->loaded() as $alias) {
            $helper = $view->helpers()->get($alias);
            if ($helper instanceof AbstractComponentHelper) {
                $counters[$alias] = Closure::bind($read, $helper, AbstractComponentHelper::class)();
            }
        }

        return $counters;
    }

    /**
     * Restores auto-id counters into the component helpers of a fresh View.
     *
     * @param \Cake\View\View $view View.
     * @param array<string, array<string, int>> $idCounters Id counters by helper alias.
     * @return void
     */
    private function seedIdCounters(View $view, array $idCounters): void
    {
        $write = function (array $counters): void {
            $this->idCounters = $counters;
        };
        foreach ($idCounters as $alias => $counters) {
            if (!$view->helpers()->has($alias)) {
                continue;
            }
            $helper = $view->helpers()->get($alias);
            if ($helper instanceof AbstractComponentHelper) {
                Closure::bind($write, $helper, AbstractComponentHelper::class)($counters);
            }
        }
    }

    /**
     * Catalog home: `pages/index.php` inside the theme's `default` layout.
     *
     * @param array<array<string, mixed>> $menu Sidebar menu.
     * @return string
     */
    private function renderHome(array $menu): string
    {
        $view = $this->view();
        $view->setTheme('BasecoatTheme');
        $view->setLayout('default');
        $view->set([
            'brand' => $this->brand(),
            'sidebarMenu' => $menu,
        ]);
        $view->append('script', $this->catalogAssets());
        $content = $this->evaluateStrict($view, $this->pagesDir . '/index.php', sprintf('Page `%s`', $this->pagesDir . '/index.php'));

        return $this->relativize($view->renderLayout($content), 0);
    }

    /**
     * A catalog page in `templates/layout.php`.
     *
     * @param string $path Page path relative to the output directory.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param array<array<string, mixed>> $menu Sidebar menu.
     * @param bool $charts Whether the page needs Chart.js.
     * @return string
     */
    private function renderLayout(string $path, string $title, string $content, array $menu, bool $charts): string
    {
        $html = $this->renderTemplate($this->view(), 'layout', [
            'title' => $title,
            'content' => $content,
            'menu' => $menu,
            'brand' => $this->brand(),
            'charts' => $charts,
            'catalogAssets' => $this->catalogAssets(),
        ]);

        return $this->relativize($html, substr_count($path, '/'));
    }

    /**
     * Highlight.js and catalog assets for `<head>`.
     *
     * @return string
     */
    private function catalogAssets(): string
    {
        $cdn = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/';
        // Subresource Integrity: sha384 of the exact cdnjs files, computed with
        // `curl -sL <url> | openssl dgst -sha384 -binary | openssl base64 -A`. Update with the version.
        $sri = fn(string $hash): string => ' integrity="sha384-' . $hash . '" crossorigin="anonymous" referrerpolicy="no-referrer"';

        return implode("\n", [
            // No favicon: an empty icon stops the browser requesting /favicon.ico outside the site path.
            '<link rel="icon" href="data:,">',
            '<link rel="stylesheet" href="' . $cdn . 'styles/github.min.css"' . $sri('eFTL69TLRZTkNfYZOLM+G04821K1qZao/4QLJbet1pP4tcF+fdXq/9CdqAbWRl/L') . ' data-hljs-theme="light">',
            '<link rel="stylesheet" href="' . $cdn . 'styles/github-dark.min.css"' . $sri('wH75j6z1lH97ZOpMOInqhgKzFkAInZPPSPlZpYKYTOqsaizPvhQZmAtLcPKXpLyH') . ' data-hljs-theme="dark" disabled>',
            '<link rel="stylesheet" href="/assets/catalog.css">',
            '<script src="' . $cdn . 'highlight.min.js"' . $sri('F/bZzf7p3Joyp5psL90p/p89AZJsndkSoGwRpXcZhleCWhd8SnRuoYo4d0yirjJp') . ' defer></script>',
            '<script src="' . $cdn . 'languages/php.min.js"' . $sri('c1RNlWYUPEU/QhgCUumvQSdSFaq+yFhv3VfGTG/OTh8oirAi/Jnp6XbnqOLePgjg') . ' defer></script>',
            '<script src="' . $cdn . 'languages/xml.min.js"' . $sri('jgkY4GMNWfQcLLIoP1vg3FWXflDrRhcSXGBW6ONIWC2SOIv5H1Pa57sXs+aomCuZ') . ' defer></script>',
            '<script src="/assets/catalog.js" defer></script>',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function brand(): array
    {
        return ['name' => 'Basecoat Theme', 'url' => '/index.html', 'icon' => 'layout-dashboard'];
    }

    /**
     * Sidebar menu: overview, components index, the Screens group, then one group per registry group.
     *
     * @param array<string, array{entry: array<string, string>, examples: list<\BasecoatTheme\Docs\ExampleFile>}> $helpers Helpers with a page.
     * @param array<string, array{title: string, description: string, controller: string, path: string, icon?: string}> $screens Screens with a page.
     * @param string $current Path of the current page (marked `aria-current`).
     * @return array<array<string, mixed>>
     */
    private function menu(array $helpers, array $screens, string $current): array
    {
        $item = fn(string $label, string $path, ?string $icon = null): array => [
            'label' => $label,
            'url' => '/' . $path,
            'icon' => $icon,
            'current' => $path === $current,
        ];
        $menu = [
            $item('Overview', 'index.html', 'house'),
            $item('All components', 'components/index.html', 'layout-dashboard'),
        ];
        $items = [$item('All screens', 'screens/index.html')];
        foreach ($screens as $slug => $screen) {
            $items[] = $item($screen['title'], 'screens/' . $slug . '.html');
        }
        $menu[] = ['type' => 'group', 'label' => 'Screens', 'items' => $items];
        foreach ($this->groups($helpers) as $group => $entries) {
            $items = [];
            foreach ($entries as $slug => $entry) {
                $items[] = $item($entry['title'], 'components/' . $slug . '.html');
            }
            $menu[] = ['type' => 'group', 'label' => $group, 'items' => $items];
        }

        return $menu;
    }

    /**
     * Registry entries with a page, by group (registry order).
     *
     * @param array<string, array{entry: array<string, string>, examples: list<\BasecoatTheme\Docs\ExampleFile>}> $helpers Helpers with a page.
     * @return array<string, array<string, array<string, string>>>
     */
    private function groups(array $helpers): array
    {
        $groups = [];
        foreach ($helpers as $slug => $helper) {
            $groups[$helper['entry']['group']][$slug] = $helper['entry'];
        }

        return $groups;
    }

    /**
     * Fresh View with every Basecoat helper loaded and its own request.
     *
     * @param string $controller Controller of the request (its `index` action).
     * @return \Cake\View\View
     */
    private function view(string $controller = 'Articles'): View
    {
        // Examples run as if rendered by `ArticlesController::index()` (screens by their own
        // controller), so array URLs, sort links and pagination resolve against the fallback routes.
        $request = new ServerRequest([
            'url' => '/' . Inflector::dasherize($controller) . '/index',
            'params' => ['plugin' => null, 'controller' => $controller, 'action' => 'index', 'pass' => []],
            'base' => '',
            'webroot' => '/',
            'session' => new Session(),
        ]);
        Router::setRequest($request);

        $view = new View($request);
        foreach ($this->helperMap() as $alias => $className) {
            $config = ['className' => $className];
            if ($alias === 'Theme') {
                $config['pluginJs'] = '/assets/basecoat-theme.js';
            }
            $view->loadHelper($alias, $config);
        }

        return $view;
    }

    /**
     * `BasecoatHelpers::MAP` (Form included) plus registry helpers whose class exists.
     *
     * @return array<string, string>
     */
    private function helperMap(): array
    {
        $map = BasecoatHelpers::MAP;
        foreach ($this->registry as $entry) {
            if (!isset($map[$entry['alias']]) && class_exists(self::HELPER_NAMESPACE . $entry['alias'] . 'Helper')) {
                $map[$entry['alias']] = 'BasecoatTheme.' . $entry['alias'];
            }
        }

        return $map;
    }

    /**
     * Routes for examples that build array URLs.
     *
     * @return void
     */
    private function setUpRouter(): void
    {
        Router::reload();
        Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);
            $routes->fallbacks();
        });
    }

    /**
     * Renders a catalog template (`templates/<name>.php`) in a View.
     *
     * @param \Cake\View\View $view View.
     * @param string $name Template name.
     * @param array<string, mixed> $vars Template variables.
     * @return string
     */
    private function renderTemplate(View $view, string $name, array $vars): string
    {
        return $this->evaluate($view, self::TEMPLATES . $name . '.php', $vars);
    }

    /**
     * Includes a PHP file as a template of the View (`$this` is the View).
     *
     * @param \Cake\View\View $view View.
     * @param string $file File.
     * @param array<string, mixed> $vars Variables.
     * @return string
     */
    private function evaluate(View $view, string $file, array $vars): string
    {
        return (fn(string $file, array $vars): string => $this->_evaluate($file, $vars))->call($view, $file, $vars);
    }

    /**
     * Makes root-relative `href`, `src` and `action` URLs relative to a page `$depth` folders deep.
     *
     * @param string $html HTML.
     * @param int $depth Folder depth of the page.
     * @return string
     */
    private function relativize(string $html, int $depth): string
    {
        $prefix = str_repeat('../', $depth);

        return (string)preg_replace_callback(
            '/(?<=\s)(href|src|action)="\/(?!\/)([^"]*)"/',
            fn(array $m): string => $m[1] . '="' . (($prefix . $m[2]) ?: './') . '"',
            $html,
        );
    }

    /**
     * Empties the output directory (after safeOutputDir()) and returns its resolved path.
     *
     * @param string $outDir Output directory.
     * @return string
     * @throws \RuntimeException When the directory is not a folder inside `build/`.
     */
    private function clearOutputDir(string $outDir): string
    {
        $target = self::safeOutputDir($outDir);
        if (is_dir($target)) {
            $this->emptyDir($target);
        }

        return $target;
    }

    /**
     * Deletes everything inside a directory (only call it on safeOutputDir() paths or below).
     *
     * @param string $dir Directory.
     * @return void
     */
    private function emptyDir(string $dir): void
    {
        // Symlinks inside are removed, never followed (no FOLLOW_SYMLINKS).
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $path = $file->getPathname();
            $file->isDir() && !$file->isLink() ? rmdir($path) : unlink($path);
        }
    }

    /**
     * Validates an output directory: it must be strictly inside the repository's `build/` folder
     * (`build/` itself is refused), after resolving `.` and `..` segments, and no part of it below
     * `build/` may be a symbolic link. Nothing is touched on disk.
     *
     * @param string $outDir Output directory (absolute, or relative to the working directory).
     * @return string Absolute path of the directory.
     * @throws \RuntimeException When the directory is not allowed.
     */
    public static function safeOutputDir(string $outDir): string
    {
        $refuse = fn(string $why): RuntimeException => new RuntimeException(sprintf(
            'Refusing to use `%s` as catalog output directory: %s. Use a folder inside `%s`, e.g. build/gh-pages.',
            $outDir,
            $why,
            self::normalize(self::REPO_ROOT . '/build'),
        ));

        $repo = realpath(self::REPO_ROOT);
        if ($repo === false) {
            throw $refuse('the repository root cannot be resolved');
        }
        $buildRoots = array_unique(array_filter([self::normalize(self::REPO_ROOT . '/build'), $repo . '/build']));
        $target = self::normalize(str_starts_with($outDir, '/') ? $outDir : getcwd() . '/' . $outDir);

        foreach ($buildRoots as $buildRoot) {
            if (!str_starts_with($target, $buildRoot . '/')) {
                continue;
            }
            if (is_link($buildRoot)) {
                throw $refuse('`build/` is a symbolic link');
            }
            $relative = substr($target, strlen($buildRoot) + 1);
            $path = $repo . '/build';
            foreach (explode('/', $relative) as $segment) {
                $path .= '/' . $segment;
                if (is_link($path)) {
                    throw $refuse('`' . $path . '` is a symbolic link');
                }
            }

            return $path;
        }

        throw $refuse('it is not strictly inside the build folder');
    }

    /**
     * Absolute path with `.` / `..` segments and duplicate slashes resolved lexically (no symlinks).
     *
     * @param string $path Absolute path.
     * @return string
     */
    private static function normalize(string $path): string
    {
        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }

        return '/' . implode('/', $parts);
    }

    /**
     * Copies the catalog assets and the plugin script to `assets/`.
     *
     * @param string $outDir Output directory.
     * @return void
     */
    private function copyAssets(string $outDir): void
    {
        $files = glob(self::ASSETS . '*') ?: [];
        $files[] = self::PLUGIN_JS;
        foreach ($files as $file) {
            $this->write($outDir, 'assets/' . basename($file), (string)file_get_contents($file));
        }
    }

    /**
     * Writes a file, creating its folder.
     *
     * @param string $outDir Output directory.
     * @param string $path Relative path.
     * @param string $contents Contents.
     * @return void
     */
    private function write(string $outDir, string $path, string $contents): void
    {
        $file = $outDir . '/' . $path;
        if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0777, true) && !is_dir(dirname($file))) {
            throw new RuntimeException(sprintf('Cannot create directory `%s`.', dirname($file)));
        }
        if (file_put_contents($file, $contents) === false) {
            throw new RuntimeException(sprintf('Cannot write `%s`.', $file));
        }
    }
}
