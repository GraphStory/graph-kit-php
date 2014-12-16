<?php

$config = array();

if (getenv('GRAPHSTORY_URL')) {

    $url = parse_url(GRAPHSTORY_URL);

    $config = array(
        'graphStory' => array(
            'restUsername' => $url['user'],
            'restPassword' => $url['pass'],
            'restHost' => $url['host'],
            'restPort' => $url['port'],
            'https' => true,
        ),
    );
}

return $config;
