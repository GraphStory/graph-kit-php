<?php

namespace GraphStory\GraphKit\Tests;

use Everyman\Neo4j\Client;
use GraphStory\GraphKit\Neo4jClient;

class GraphKitTestCase extends \PHPUnit_Framework_TestCase
{
    protected function buildRealClient()
    {
        $neo4jClient = new \Everyman\Neo4j\Client('localhost', 7474);

        return $neo4jClient;
    }
}