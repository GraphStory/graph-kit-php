<?php

if (file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    $loader = require $file;
    $loader->register();
} else {
    throw new \RuntimeException('Composer autoloader can not be found, did you omit to run the "composer install command" ?');
}
