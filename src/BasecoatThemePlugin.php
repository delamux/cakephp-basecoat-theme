<?php
declare(strict_types=1);

namespace BasecoatTheme;

use BasecoatTheme\Bake\SearchFiltersListener;
use BasecoatTheme\Command\BakeScreenCommand;
use Bake\Command\BakeCommand;
use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;

/**
 * BasecoatTheme plugin.
 *
 * A view plugin: it ships helpers, a theme (layout + elements), template files for core helpers
 * (Form, Paginator), bake templates and web assets. It does not register routes, middleware or
 * services. On the console it listens to bake to add list filters
 * (BasecoatTheme\Bake\SearchFiltersListener) and, when cakephp/bake is installed, adds
 * `bin/cake bake screen` (BasecoatTheme\Command\BakeScreenCommand).
 */
class BasecoatThemePlugin extends BasePlugin
{
    /**
     * @param \Cake\Core\PluginApplicationInterface $app Application.
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        if (PHP_SAPI === 'cli') {
            EventManager::instance()->on(new SearchFiltersListener());
        }
    }

    /**
     * Adds `bake screen` when cakephp/bake is installed; no other command (no command discovery,
     * which would autoload BakeScreenCommand without its parent class).
     *
     * @param \Cake\Console\CommandCollection $commands Commands.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        if (class_exists(BakeCommand::class)) {
            $commands->add(BakeScreenCommand::defaultName(), BakeScreenCommand::class);
        }

        return $commands;
    }

    protected bool $middlewareEnabled = false;

    protected bool $servicesEnabled = false;

    protected bool $routesEnabled = false;

    protected bool $eventsEnabled = false;
}
