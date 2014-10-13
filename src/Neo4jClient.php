<?php

namespace GraphStory\GraphKit;

use Everyman\Neo4j\Client;

class Neo4jClient
{
    protected static $client = null;

    public static function client()
    {
        return self::$client;
    }

    public static function setClient(Client $client)
    {
        self::$client = $client;
    }
}
