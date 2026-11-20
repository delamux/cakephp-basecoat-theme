<?php
declare(strict_types=1);

namespace TestApp;

use Cake\Console\CommandCollection;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use TestApp\Command\ScratchBakeScreenCommand;

/**
 * Test application for console integration tests (`bin/cake bake screen`): loads Bake and the
 * plugin, and swaps `bake screen` for ScratchBakeScreenCommand (app `src/` output under TMP).
 */
class Application extends BaseApplication
{
    /**
     * @return void
     */
    public function bootstrap(): void
    {
        $this->addPlugin('Bake');
        $this->addPlugin('BasecoatTheme');
    }

    /**
     * @param \Cake\Routing\RouteBuilder $routes Routes.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue Middleware queue.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * Plugin commands, then `bake screen` replaced by the scratch variant (plugins' console()
     * hooks run after the application's, so the swap happens here).
     *
     * @param \Cake\Console\CommandCollection $commands Commands.
     * @return \Cake\Console\CommandCollection
     */
    public function pluginConsole(CommandCollection $commands): CommandCollection
    {
        $commands = parent::pluginConsole($commands);
        $commands->remove('bake screen');

        return $commands->add('bake screen', ScratchBakeScreenCommand::class);
    }
}
