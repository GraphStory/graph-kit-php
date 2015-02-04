<?php

namespace GraphStory\GraphKit\Model;

class User
{
    public $uuid;

    public $username;

    public $firstname;

    public $lastname;

    public $commonFriends;

    public function toArray()
    {
        return array(
            'node' => $this->node,
            'id' => $this->id,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'commonFriends' => $this->commonFriends,
        );
    }
}
