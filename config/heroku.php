<?php

$config = array();

if ($url = getenv('GRAPHSTORY_URL')) {

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
