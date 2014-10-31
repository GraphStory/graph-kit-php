<?php

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1')) || php_sapi_name() === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

require_once __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

use Zend\Config\Factory as ConfigFactory;

if (!defined('SLIM_MODE')) {
    $mode = getenv('SLIM_MODE') ? getenv('SLIM_MODE') : 'development';
    define('SLIM_MODE', $mode);
}

define('APPLICATION_PATH', realpath(dirname(__DIR__)));

$configPaths = sprintf(
    '%s/config/{,*.}{global,%s,secret}.php',
    APPLICATION_PATH,
    SLIM_MODE
);

$config = ConfigFactory::fromFiles(glob($configPaths, GLOB_BRACE));

require_once APPLICATION_PATH . '/vendor/autoload.php';

require APPLICATION_PATH . '/src/app.php';
// Run app
$app->run();