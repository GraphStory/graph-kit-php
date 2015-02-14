<?php

namespace GraphStory\GraphKit\Model;

class Content
{
    /**
     * @var
     */
    protected $id;

    /**
     * @var
     */
    protected $contentId;

    /**
     * @var
     */
    protected $title;

    /**
     * @var
     */
    protected $url;

    /**
     * @var
     */
    protected $tagstr;

    /**
     * @var
     */
    protected $timestamp;

    /**
     * @var User
     */
    protected $owner;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @return mixed
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * @param mixed $contentId
     */
    public function setContentId($contentId)
    {
        $this->contentId = (int) $contentId;
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
        $this->title = (string) $title;
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
        $this->url = (string) $url;
    }

    /**
     * @return mixed
     */
    public function getTagstr()
    {
        return $this->tagstr;
    }

    /**
     * @param mixed $tagstr
     */
    public function setTagstr($tagstr)
    {
        $this->tagstr = (string) $tagstr;
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
        $this->timestamp = (int) $timestamp;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'contentId' => $this->contentId,
            'title' => $this->title,
            'url' => $this->url,
            'tagstr' => $this->tagstr,
            'timestamp' => $this->timestamp,
            'owner' => $this->owner->getUsername()
        );
    }
}
