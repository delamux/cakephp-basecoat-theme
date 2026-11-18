<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View;

use BasecoatTheme\View\BasecoatHelpersTrait;
use BasecoatTheme\View\BasecoatHelpers;
use BasecoatTheme\View\Helper\ThemeHelper;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Integration: the plugin used as a theme (layout + elements + flash) and ThemeHelper.
 */
class ThemeTest extends TestCase
{
    protected ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
        Router::reload();
        $builder = Router::createRouteBuilder('/');
        $builder->scope('/', function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);
            $routes->fallbacks();
        });
        $this->request = new ServerRequest([
            'url' => '/articles',
            'params' => ['plugin' => null, 'controller' => 'Articles', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
            'session' => new Session(),
        ]);
        Router::setRequest($this->request);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    public function testDefaultLayoutWithThemeRendersDashboard(): void
    {
        $this->request->getFlash()->success('Article saved');
        $view = new View($this->request, null, null, [
            'theme' => 'BasecoatTheme',
            'templatePath' => 'Articles',
            'template' => 'index',
            'viewVars' => [
                'brand' => ['name' => 'Acme Inc.', 'url' => '/', 'icon' => 'circle'],
                'sidebarMenu' => [
                    ['label' => 'Dashboard', 'url' => '/articles', 'icon' => 'layout-dashboard'],
                    ['type' => 'group', 'label' => 'Documents', 'items' => [['label' => 'Reports', 'url' => '/reports', 'icon' => 'file-chart']]],
                ],
                'sidebarUser' => ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'menu' => [['label' => 'Log out', 'url' => '/logout', 'icon' => 'log-out']]],
            ],
        ]);
        $html = $view->render();

        $this->assertStringContainsString('<title>Documents</title>', $html);
        $this->assertStringContainsString('basecoat-css@1.1.0/dist/basecoat.cdn.min.css', $html);
        $this->assertStringContainsString('chart.umd.js', $html, 'Chart.js is loaded because the page rendered a chart');
        $this->assertStringContainsString('<script src="/basecoat_theme/js/basecoat-theme.js" defer="defer"></script>', $html);
        $this->assertStringContainsString('<aside id="sidebar" data-side="left" aria-hidden="false" class="sidebar">', $html);
        $this->assertStringContainsString('<span>Acme Inc.</span>', $html);
        $this->assertStringContainsString('<a href="/articles" aria-current="page">', $html);
        $this->assertStringContainsString('id="user-menu"', $html);
        $this->assertMatchesRegularExpression('#<div[^>]*role="alert"[^>]*class="alert"><svg.*<h2>Article saved</h2>#s', $html);
        $this->assertStringContainsString('data-theme-toggle="true"', $html);
        $this->assertStringContainsString('<div id="toaster" class="toaster"></div>', $html);
        $this->assertStringContainsString('id="sections-tabs"', $html);
        $this->assertSame(4, substr_count($html, 'bg-linear-to-t from-primary/5'));
        $this->assertSame(5, substr_count($html, 'class="dropdown-menu"'));
    }

    public function testFlashIsEscapedAndSupportsParams(): void
    {
        $this->request->getFlash()->error('<b>Bad</b>', ['params' => ['title' => 'Error', 'dismissible' => true]]);
        $this->request->getFlash()->set('<i>Raw</i>', ['element' => 'warning', 'params' => ['escape' => false]]);
        $view = new View($this->request, null, null, ['theme' => 'BasecoatTheme']);
        $view->loadHelper('Flash');
        $html = $view->Flash->render();

        $this->assertStringContainsString('data-variant="destructive"', $html);
        $this->assertStringContainsString('<h2>Error</h2><section>&lt;b&gt;Bad&lt;/b&gt;</section>', $html);
        $this->assertStringContainsString('Dismiss</button>', $html);
        $this->assertStringContainsString('<h2><i>Raw</i></h2>', $html);
    }

    public function testToastFlashElementFromPlugin(): void
    {
        $this->request->getFlash()->set('Copied', ['element' => 'toast', 'plugin' => 'BasecoatTheme', 'params' => ['category' => 'info']]);
        $view = new View($this->request);
        $view->loadHelper('Flash');
        $html = $view->Flash->render();
        $this->assertStringContainsString('<template data-basecoat-toast data-toaster="toaster">', $html);
        $this->assertStringNotContainsString('<script', $html, 'Moved by basecoat-theme.js, no inline script');
        $this->assertStringContainsString('data-category="info"', $html);
    }

    public function testAuthLayout(): void
    {
        $view = new View($this->request, null, null, ['theme' => 'BasecoatTheme', 'layout' => 'auth']);
        $html = $view->renderLayout('<form></form>');
        $this->assertStringContainsString('<div class="flex w-full max-w-sm flex-col gap-6">', $html);
        $this->assertStringContainsString('<form></form>', $html);
        $this->assertStringNotContainsString('chart.umd.js', $html);
    }

    public function testHelpersTrait(): void
    {
        $view = new class ($this->request) extends View {
            use BasecoatHelpersTrait;

            public function initialize(): void
            {
                parent::initialize();
                $this->loadBasecoatHelpers(['Button' => ['size' => 'sm'], 'Form' => false]);
            }
        };
        foreach (array_keys(BasecoatHelpers::MAP) as $alias) {
            if ($alias === 'Form') {
                continue;
            }
            $this->assertTrue($view->helpers()->has($alias), $alias);
        }
        $this->assertFalse($view->helpers()->has('Form'));
        $this->assertSame('sm', $view->Button->getConfig('size'));
    }

    public function testThemeHelperConfiguration(): void
    {
        $view = new View($this->request);
        $theme = new ThemeHelper($view, ['style' => 'nova', 'tailwindBrowser' => false, 'font' => false, 'version' => '1.0.0']);
        $css = $theme->css();
        $this->assertSame('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@1.0.0/dist/basecoat-nova.cdn.min.css">', $css);
        $scripts = $theme->scripts();
        $this->assertStringNotContainsString('tailwindcss', $scripts);
        $this->assertStringNotContainsString('chart.umd.js', $scripts);
        $this->assertStringContainsString('basecoat-css@1.0.0/dist/js/all.min.js', $scripts);

        $own = new ThemeHelper(new View($this->request), ['css' => ['app'], 'font' => false, 'tailwindBrowser' => false]);
        $this->assertSame('<link rel="stylesheet" href="/css/app.css">', trim($own->css()));
        $this->assertStringContainsString("localStorage.getItem(\"themeMode\")", $own->darkModeScript());
    }
}
