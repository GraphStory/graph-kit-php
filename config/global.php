<?php

return array(
    'slim' => array(
        'templates.path' => APPLICATION_PATH . '/templates',
        'cookies.encrypt' => true,
        'cookies.secret_key' => 'CHANGE_ME',
        'cookies.httponly' => false,
    ),
    'mustache' => array(
        'cache' => '/tmp/mustache-cache',
        'pragmas' => array(
            \Mustache_Engine::PRAGMA_BLOCKS,
        ),
    ),
    'logging' => array(
        'logFile' => APPLICATION_PATH . '/logs/app.log',
    ),
    // This is a template for the Graph Story connection info you should add 
    // to /config/secret.php
    'graphStory' => array(
        'restUsername' => null,
        'restPassword' => null,
        'restHost' => null,
        'restPort' => null,
        'https' => null,
    ),
);
