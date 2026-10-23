<?php

declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__));
define('CORE_PATH', ROOT . '/vendor/cakephp/cakephp/');
define('CAKE', CORE_PATH . 'src/');

Configure::write('App.encoding', 'UTF-8');
Configure::write('App.defaultLocale', 'en_US');
Cache::setConfig('_cake_core_', ['className' => 'Array']);
Cache::setConfig('_cake_translations_', ['className' => 'Array']);
Cache::setConfig('default', ['className' => 'Array']);
