<?php

namespace GraphStory\GraphKit\Domain;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Content")
 */
class Content
{
    /**
     * @var int
     * @OGM\GraphId()
     */
    protected $id;

    /**
     * @var string
     * @OGM\Property(type="string")
     */
    protected $title;

    /**
     * @var string
     * @OGM\Property(type="string")
     */
    protected $url;

    /**
     * @var string
     * @OGM\Property(type="string")
     */
    protected $tagStr;

    /**
     * @var int
     * @OGM\Property(type="int")
     */
    protected $timestamp;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getTagStr()
    {
        return $this->tagStr;
    }

    /**
     * @param mixed $tagStr
     */
    public function setTagStr($tagStr)
    {
        $this->tagStr = $tagStr;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }
}