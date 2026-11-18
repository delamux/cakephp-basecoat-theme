<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Shared setup for helper tests.
 */
abstract class HelperTestCase extends TestCase
{
    protected View $View;

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

        $request = new ServerRequest([
            'url' => '/articles/index',
            'params' => ['plugin' => null, 'controller' => 'Articles', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
        ]);
        Router::setRequest($request);
        $this->View = new View($request);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        unset($this->View);
    }

    /**
     * Normalizes whitespace between tags to make assertions readable.
     */
    protected function squash(string $html): string
    {
        return trim((string)preg_replace('/>\s+</', '><', $html));
    }
}
