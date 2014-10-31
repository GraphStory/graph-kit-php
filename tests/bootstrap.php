<?php

if (file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    $loader = require $file;
    $loader->register();
} else {
    throw new \RuntimeException('Composer autoloader can not be found, did you omit to run the "composer install command" ?');
}

define('SLIM_MODE', 'test');
define('APPLICATION_PATH', realpath(dirname(__DIR__)));
// The following directory will be used for logging and temp files tests
define('TEMP_DIR', sys_get_temp_dir());