<?php
declare(strict_types=1);

/**
 * CakePHP bootstrap for the catalog generator (mirrors tests/bootstrap.php).
 */
use BasecoatTheme\BasecoatThemePlugin;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;

$root = dirname(__DIR__, 2);
chdir($root);

define('ROOT', $root);
define('APP_DIR', 'TestApp');
define('APP', ROOT . '/tests/test_app/src/');
define('CONFIG', ROOT . '/tests/test_app/config/');
define('WWW_ROOT', ROOT . '/webroot/');
define('TMP', sys_get_temp_dir() . '/basecoat-theme-catalog/');
define('CACHE', TMP . 'cache/');
define('LOGS', TMP . 'logs/');
define('CORE_PATH', ROOT . '/vendor/cakephp/cakephp/');
define('CAKE', CORE_PATH . 'src/');

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
require CAKE . 'functions.php';

error_reporting(E_ALL);
Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'TestApp',
    'encoding' => 'UTF-8',
    'base' => '',
    'baseUrl' => false,
    'dir' => 'src',
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => [
        'plugins' => [],
        'templates' => [],
    ],
]);

Cache::setConfig([
    '_cake_core_' => ['engine' => 'Array'],
    '_cake_translations_' => ['engine' => 'Array'],
    '_cake_model_' => ['engine' => 'Array'],
]);

Plugin::getCollection()->add(new BasecoatThemePlugin());
