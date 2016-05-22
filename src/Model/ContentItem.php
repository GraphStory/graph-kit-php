<?php

namespace GraphStory\GraphKit\Model;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\QueryResult()
 */
class ContentItem
{
    /**
     * @OGM\MappedResult(type="ENTITY", target="\GraphStory\GraphKit\Domain\Content")
     */
    protected $post;

    /**
     * @OGM\MappedResult(type="ENTITY", target="\GraphStory\GraphKit\Domain\User")
     */
    protected $author;

    /**
     * @OGM\MappedResult(type="BOOLEAN")
     */
    protected $isOwner;

    /**
     * @return mixed
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     */
    public function getIsOwner()
    {
        return $this->isOwner;
    }
}