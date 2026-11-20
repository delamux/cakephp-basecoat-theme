<?php
declare(strict_types=1);

namespace BasecoatTheme;

use BasecoatTheme\Bake\SearchFiltersListener;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;

/**
 * BasecoatTheme plugin.
 *
 * A view plugin: it ships helpers, a theme (layout + elements), template files for core helpers
 * (Form, Paginator), bake templates and web assets. It does not register routes, middleware,
 * commands or services. On the console it listens to bake to add list filters
 * (BasecoatTheme\Bake\SearchFiltersListener).
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

    protected bool $consoleEnabled = false;

    protected bool $middlewareEnabled = false;

    protected bool $servicesEnabled = false;

    protected bool $routesEnabled = false;

    protected bool $eventsEnabled = false;
}
