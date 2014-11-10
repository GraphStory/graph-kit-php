<?php

if (!defined('SLIM_MODE')) {
    define('SLIM_MODE', getenv('SLIM_MODE') ?: 'production');
}

define('APPLICATION_PATH', realpath(dirname(__DIR__)));

require_once __DIR__.'/../vendor/autoload.php';

require_once APPLICATION_PATH . '/vendor/autoload.php';

use Zend\Config\Factory as ConfigFactory;

$configPaths = sprintf(
    '%s/config/{,*.}{global,%s,secret}.php',
    APPLICATION_PATH,
    SLIM_MODE
);

$config = ConfigFactory::fromFiles(glob($configPaths, GLOB_BRACE));

require_once APPLICATION_PATH . '/src/app.php';
// Run app
$app->run();
