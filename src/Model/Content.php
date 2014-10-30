<?php

namespace GraphStory\GraphKit\Model;

class Content
{
    public $node;
    public $id;
    public $title;
    public $url;
    public $tagstr;
    public $timestamp;
    public $uuid;
    public $contentId;
    public $userNameForPost;
    public $owner = false;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'tagstr' => $this->tagstr,
            'timestamp' => $this->timestamp,
            'uuid' => $this->uuid,
            'contentId' => $this->uuid,
            'userNameForPost' => $this->userNameForPost,
            'owner' => $this->owner,
            'node' => $this->node,
        );
    }
}
