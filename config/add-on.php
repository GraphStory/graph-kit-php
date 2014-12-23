<?php

$config = array();

$url = isset($_SERVER['GRAPHSTORY_URL']) ? $_SERVER['GRAPHSTORY_URL'] : getenv('GRAPHSTORY_URL');

if ($url) {
    $graphstory = parse_url($url);

    $config = array(
        'graphStory' => array(
            'restUsername' => $graphstory['user'],
            'restPassword' => $graphstory['pass'],
            'restHost' => $graphstory['host'],
            'restPort' => $graphstory['port'],
            'https' => true,
        ),
    );
}

return $config;
