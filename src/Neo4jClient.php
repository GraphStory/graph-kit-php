<?php

namespace GraphStory\GraphKit;

use Neoxygen\NeoClient\Client;

class Neo4jClient
{
    protected static $client = null;

    /**
     * @return \Neoxygen\NeoClient\Client|null
     */
    public static function client()
    {
        return self::$client;
    }

    public static function setClient(Client $client)
    {
        self::$client = $client;
    }
}
