<?php

# PHP_ENV is present for Engine Yard compatibility
if (defined('PHP_ENV')) {
    define('SLIM_MODE', getenv('PHP_ENV'));
}

if (isset($_SERVER['PHP_ENV'])) {
    define('SLIM_MODE', $_SERVER['PHP_ENV']);
}

if (!defined('SLIM_MODE')) {
    define('SLIM_MODE', getenv('SLIM_MODE') ?: 'production');
}

define('APPLICATION_PATH', realpath(dirname(__DIR__)));

$loader = require_once __DIR__.'/../vendor/autoload.php';
\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

use Zend\Config\Factory as ConfigFactory;

$configPaths = sprintf(
    '%s/config/{,*.}{global,%s,secret,add-on}.php',
    APPLICATION_PATH,
    SLIM_MODE
);

$config = ConfigFactory::fromFiles(glob($configPaths, GLOB_BRACE));

require_once APPLICATION_PATH . '/src/app.php';
// Run app
$app->run();
