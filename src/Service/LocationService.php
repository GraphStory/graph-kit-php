<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Node;
use GraphStory\GraphKit\Model\Location;

class LocationService
{
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
