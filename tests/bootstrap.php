<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Routing\Router;

/**
 * Minimal bootstrap to test the plugin in isolation.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

define('ROOT', $root);
define('APP_DIR', 'TestApp');
define('APP', ROOT . '/tests/test_app/src/');
define('CONFIG', ROOT . '/tests/test_app/config/');
define('WWW_ROOT', ROOT . '/webroot/');
define('TMP', sys_get_temp_dir() . '/basecoat-theme-tests/');
define('CACHE', TMP . 'cache/');
define('LOGS', TMP . 'logs/');
define('CORE_PATH', ROOT . '/vendor/cakephp/cakephp/');
define('CAKE', CORE_PATH . 'src/');

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
// Global function aliases (h(), __d()...) as loaded by cakephp/app.
require CAKE . 'functions.php';

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'TestApp',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => 'src',
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => [
        'plugins' => [ROOT . '/tests/test_app/plugins/'],
        'templates' => [ROOT . '/tests/test_app/templates/'],
    ],
]);

Cache::setConfig([
    '_cake_core_' => ['engine' => 'Array'],
    '_cake_translations_' => ['engine' => 'Array'],
    '_cake_model_' => ['engine' => 'Array'],
]);

Router::reload();
