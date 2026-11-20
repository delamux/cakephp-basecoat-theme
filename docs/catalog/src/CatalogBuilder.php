<?php
declare(strict_types=1);

namespace BasecoatTheme\Docs;

use BasecoatTheme\View\BasecoatHelpers;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\View\View;
use ErrorException;
use RuntimeException;
use Throwable;

/**
 * Builds the static component catalog from the example files and the real helpers.
 *
 * Output (all links relative, so the site works under any sub-path):
 * - `index.html`: `pages/index.php` rendered with the theme's `default` layout;
 * - `components/index.html`: helpers grouped as in the registry;
 * - `components/<slug>.html`: one page per helper with its examples (preview, PHP, HTML);
 * - `assets/`: catalog CSS/JS and the plugin's `basecoat-theme.js`.
 *
 * Every example runs in a fresh View; any PHP warning, notice, deprecation or exception fails the build.
 */
final class CatalogBuilder
{
    private const HELPER_NAMESPACE = 'BasecoatTheme\\View\\Helper\\';

    private const TEMPLATES = __DIR__ . '/../templates/';

    private const ASSETS = __DIR__ . '/../assets/';

    private const PLUGIN_JS = __DIR__ . '/../../../webroot/js/basecoat-theme.js';

    /**
     * @param string $examplesDir Directory with one folder of examples per helper slug.
     * @param string $pagesDir Directory with `index.php` (catalog home).
     * @param array<string, array{alias: string, title: string, group: string, intro: string}> $registry Helpers by slug.
     */
    public function __construct(
        private readonly string $examplesDir,
        private readonly string $pagesDir,
        private readonly array $registry,
    ) {
    }

    /**
     * Builds the site.
     *
     * @param string $outDir Output directory.
     * @param bool $strict Fail on warnings (helper without examples or helper class missing).
     * @return \BasecoatTheme\Docs\BuildReport
     * @throws \RuntimeException When an example fails, or on any warning in strict mode.
     */
    public function build(string $outDir, bool $strict = false): BuildReport
    {
        $this->setUpRouter();
        [$helpers, $warnings] = $this->collect();
        if ($strict && $warnings) {
            throw new RuntimeException("Strict mode, " . count($warnings) . " problem(s):\n- " . implode("\n- ", $warnings));
        }

        $outDir = rtrim($outDir, '/');
        $this->copyAssets($outDir);
        $pages = [];

        foreach ($helpers as $slug => $helper) {
            $path = 'components/' . $slug . '.html';
            $blocks = '';
            $charts = false;
            foreach ($helper['examples'] as $example) {
                $preview = $this->runExample($example);
                $charts = $charts || str_contains($preview, 'data-basecoat-chart');
                $blocks .= $this->renderTemplate($this->view('/' . $path), 'example', [
                    'example' => $example,
                    'id' => 'example-' . $slug . '-' . $example->order,
                    'preview' => $preview,
                    'html' => HtmlFormatter::pretty($preview),
                ]);
            }
            $content = $this->renderTemplate($this->view('/' . $path), 'helper_page', [
                'entry' => $helper['entry'],
                'examples' => $blocks,
            ]);
            $this->write($outDir, $path, $this->renderLayout($path, $helper['entry']['title'], $content, $this->menu($helpers, $path), $charts));
            $pages[] = $path;
        }

        $path = 'components/index.html';
        $content = $this->renderTemplate($this->view('/' . $path), 'components_index', ['groups' => $this->groups($helpers)]);
        $this->write($outDir, $path, $this->renderLayout($path, 'Components', $content, $this->menu($helpers, $path), false));
        $pages[] = $path;

        $this->write($outDir, 'index.html', $this->renderHome($this->menu($helpers, 'index.html')));
        array_unshift($pages, 'index.html');

        return new BuildReport($pages, $warnings);
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
     * @param \BasecoatTheme\Docs\ExampleFile $example Example.
     * @return string
     * @throws \RuntimeException When the example throws or emits any PHP error.
     */
    private function runExample(ExampleFile $example): string
    {
        $view = $this->view('/components/' . $example->slug . '.html');
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            return $this->evaluate($view, $example->path, []);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                'Example `%s` failed: %s (%s:%d)',
                $example->path,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ), 0, $e);
        } finally {
            restore_error_handler();
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
        $view = $this->view('/index.html');
        $view->setTheme('BasecoatTheme');
        $view->setLayout('default');
        $view->set([
            'brand' => $this->brand(),
            'sidebarMenu' => $menu,
        ]);
        $view->append('script', $this->catalogAssets());
        $content = $this->evaluate($view, $this->pagesDir . '/index.php', []);

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
        $html = $this->renderTemplate($this->view('/' . $path), 'layout', [
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

        return implode("\n", [
            '<link rel="stylesheet" href="' . $cdn . 'styles/github.min.css" data-hljs-theme="light">',
            '<link rel="stylesheet" href="' . $cdn . 'styles/github-dark.min.css" data-hljs-theme="dark" disabled>',
            '<link rel="stylesheet" href="/assets/catalog.css">',
            '<script src="' . $cdn . 'highlight.min.js" defer></script>',
            '<script src="' . $cdn . 'languages/php.min.js" defer></script>',
            '<script src="' . $cdn . 'languages/xml.min.js" defer></script>',
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
     * Sidebar menu: overview, components index and one group per registry group.
     *
     * @param array<string, array{entry: array<string, string>, examples: list<\BasecoatTheme\Docs\ExampleFile>}> $helpers Helpers with a page.
     * @param string $current Path of the current page (marked `aria-current`).
     * @return array<array<string, mixed>>
     */
    private function menu(array $helpers, string $current): array
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
     * Fresh View for a page path, with every Basecoat helper loaded.
     *
     * @param string $path Root-relative path of the page (e.g. `/components/badge.html`).
     * @return \Cake\View\View
     */
    private function view(string $path): View
    {
        $request = new ServerRequest([
            'url' => $path,
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
