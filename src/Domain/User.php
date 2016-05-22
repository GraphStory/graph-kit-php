<?php

namespace GraphStory\GraphKit\Domain;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="User")
 */
class User
{
    /**
     * @OGM\GraphId()
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $username;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $firstname;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $lastname;

    /**
     * @OGM\Relationship(type="FOLLOWS", direction="OUTGOING", collection=true, targetEntity="GraphStory\GraphKit\Domain\User", mappedBy="followers")
     * @var \GraphStory\GraphKit\Domain\User[]
     */
    protected $following;

    /**
     * @OGM\Relationship(type="FOLLOWS", direction="INCOMING", collection=true, targetEntity="GraphStory\GraphKit\Domain\User", mappedBy="followers")
     * @var \GraphStory\GraphKit\Domain\User[]
     */
    protected $followers;

    /**
     * @OGM\Relationship(type="CURRENT_POST", direction="OUTGOING", targetEntity="GraphStory\GraphKit\Domain\Content")
     */
    protected $currentPost;

    public function __construct($login)
    {
        $this->followers = new ArrayCollection();
        $this->following = new ArrayCollection();
    }

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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
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
        $this->firstname = $firstname;
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
        $this->lastname = $lastname;
    }

    /**
     * @return mixed
     */
    public function getFollowing()
    {
        return $this->following;
    }

    public function addFollowing(User $user)
    {
        if (!in_array($user, $this->following)) {
            $this->following[] = $user;
        }
    }

    /**
     * @param User[] $following
     */
    public function setFollowing(array $following)
    {
        $this->following = $following;
    }

    /**
     * @return User[]
     */
    public function getFollowers()
    {
        return $this->followers;
    }

    public function addFollower(User $user)
    {
        if (!in_array($user, $this->followers)) {
            $this->followers[] = $user;
        }
    }

    /**
     * @param User[] $followers
     */
    public function setFollowers(array $followers)
    {
        $this->followers = $followers;
    }

    public function setCurrentPost(Content $content)
    {
        $this->currentPost = $content;
    }

}
