<?php

namespace GraphStory\GraphKit\Tests;

class GraphKitTest extends GraphKitTestCase
{
    public function testBuildClient()
    {
        $client = $this->buildRealClient();

        $this->assertInstanceOf('Everyman\Neo4j\Client', $client);
    }
}