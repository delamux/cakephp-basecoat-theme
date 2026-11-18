<?php
declare(strict_types=1);

namespace BasecoatTheme;

use Cake\Core\BasePlugin;

/**
 * BasecoatTheme plugin.
 *
 * A view-only plugin: it ships helpers, a theme (layout + elements),
 * template files for core helpers (Form, Paginator) and web assets.
 * It does not register routes, middleware, commands or services.
 */
class BasecoatThemePlugin extends BasePlugin
{
    protected bool $bootstrapEnabled = false;

    protected bool $consoleEnabled = false;

    protected bool $middlewareEnabled = false;

    protected bool $servicesEnabled = false;

    protected bool $routesEnabled = false;

    protected bool $eventsEnabled = false;
}
