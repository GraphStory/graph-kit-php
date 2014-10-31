<?php

namespace GraphStory\GraphKit\Model;

class Content
{
    public $node;
    public $nodeId;
    public $contentId;
    public $title;
    public $url;
    public $tagstr;
    public $timestamp;
    public $userNameForPost;
    public $owner = false;

    public function toArray()
    {
        return array(
            'node' => $this->node,
            'nodeId' => $this->nodeId,
            'contentId' => $this->contentId,
            'title' => $this->title,
            'url' => $this->url,
            'tagstr' => $this->tagstr,
            'timestamp' => $this->timestamp,
            'userNameForPost' => $this->userNameForPost,
            'owner' => $this->owner,
        );
    }
}
