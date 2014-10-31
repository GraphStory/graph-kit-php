<?php

namespace GraphStory\GraphKit\Test\Unit;

use GraphStory\GraphKit\Neo4jClient;
use GraphStory\GraphKit\Test\Mock\Neo4jClientMock;

class Neo4jClientTest extends \PHPUnit_Framework_TestCase
{
    private $mockClient;

    public function testSetClient()
    {
        $client = $this->getMockClient();
        Neo4jClient::setClient($client);
        $this->assertSame($client, Neo4jClient::client());
    }

    public function getMockClient()
    {
        if (!$this->mockClient) {
            $this->mockClient = new Neo4jClientMock();

            return $this->mockClient;
        }
    }
}