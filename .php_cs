# vi: ft=php
<?php

require_once './vendor/autoload.php';

$finder = \Symfony\CS\Finder\DefaultFinder::create()
    ->in('config/')
    ->in('public/')
    ->in('src/')
    ->in('tests/');

return \Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers([
        '-concat_without_spaces',
        'concat_with_spaces',
        'ordered_use',
    ])
    ->finder($finder);
