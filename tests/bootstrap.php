<?php

// define needed constants
define('SLIM_MODE', 'test');
define('APPLICATION_PATH', realpath(dirname(__DIR__)));

// change directories so that we are in the root of the project
chdir(APPLICATION_PATH);

// pull in all of the dependencies included via Composer
require 'vendor/autoload.php';
