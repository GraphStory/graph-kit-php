<?php

namespace GraphStory\GraphKit\Model;

class User
{
    /**
     * @var int|null $id
     */
    protected $id;

    /**
     * @var string|null $username
     */
    protected $username;

    /**
     * @var string|null $firstname
     */
    protected $firstname;

    /**
     * @var string|null $lastname
     */
    protected $lastname;

    /**
     * @param $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = (string) $username;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = (string) $firstname;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = (string) $lastname;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname
        );
    }
}
