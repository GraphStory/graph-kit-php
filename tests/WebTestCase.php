<?php

namespace GraphStory\GraphKit\Test;

use Slim\Slim;
use There4\Slim\Test\WebTestCase as There4WebTestCase;
use Zend\Config\Factory as ConfigFactory;

class WebTestCase extends There4WebTestCase
{
    public function getSlimInstance()
    {
        $configPaths = sprintf(
            '%s/config/{,*.}{global,%s,secret}.php',
            APPLICATION_PATH,
            SLIM_MODE
        );

        $config = ConfigFactory::fromFiles(glob($configPaths, GLOB_BRACE));
        $app = new Slim($config);

        // TODO: Include app.php file

        return $app;
    }

    public function getClient()
    {
        return $this->client;
    }
} 