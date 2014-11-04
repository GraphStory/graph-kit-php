<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Node;
use GraphStory\GraphKit\Model\Content;
use GraphStory\GraphKit\Model\Tag;
use GraphStory\GraphKit\Neo4jClient;

class TagService
{
    public static function getByNodeId($id)
    {
        $node = Neo4jClient::client()->getNode($id);
        $content = new Content();
        $content->id = $node->getId();
        $content->title = $node->getProperty('title');
        $content->url = $node->getProperty('url');
        $content->url = $node->getProperty('tags');
        $content->node = $node;

        return $content;
    }

    public static function fromArray(Node $node)
    {
        $tag = new Tag();
        $tag->id = $node->getId();
        $tag->tagcontent = $node->getProperty('tagcontent');
        $tag->node = $node;

        return $user;
    }
}
