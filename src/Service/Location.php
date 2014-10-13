<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Node;

class Location
{
    public $id = null;
    public $latitude = '';
    public $longitude = '';

    public static function getByNodeId($id)
    {
        $node = Neo4jClient::client()->getNode($id);
        $location = new Location();
        $location->id = $node->getId();
        $location->title = $node->getProperty('title');
        $location->url = $node->getProperty('url');
        $location->node = $node;

        return $location;
    }
}
