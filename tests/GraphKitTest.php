<?php

namespace GraphStory\GraphKit\Tests;

use GraphStory\GraphKit\Neo4jClient;

class GraphKitTest extends GraphKitTestCase
{
    public function testBuildClient()
    {
        $client = $this->buildRealClient();
        $this->assertInstanceOf('Everyman\Neo4j\Client', Neo4jClient::client());
    }
}