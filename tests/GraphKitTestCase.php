<?php

namespace GraphStory\GraphKit\Tests;

use Everyman\Neo4j\Client;
use GraphStory\GraphKit\Neo4jClient;

class GraphKitTestCase extends \PHPUnit_Framework_TestCase
{
    const SLIM_MODE = 'test';

    protected function buildRealClient()
    {
        $neo4jClient = new Client('localhost', 7474);
        $appClient = Neo4jClient::setClient($neo4jClient);

        return $appClient;
    }
}