<?php

namespace GraphStory\GraphKit\Tests\Service;

use GraphStory\GraphKit\Tests\GraphKitTestCase;
use GraphStory\GraphKit\Model\User,
    GraphStory\GraphKit\Service\UserService;

class UserServiceTest extends GraphKitTestCase
{

    private $standardUser;

    private $standardUserArray;

    public function __construct()
    {
        $user = new User();
        $user->id = 12345678;
        $user->username = 'serialgrapher';
        $user->firstname = 'Roger';
        $user->lastname = 'McCarthy';
        $this->standardUser = $user;
        $this->standardUserArray = $user->toArray();
    }

    public function testCreateAndGetUser()
    {
        $this->buildRealClient();
        $this->clearDB();
        $user = $this->standardUser;
        $userService = new UserService();
        $userService->save($user);
        $fetchedUser = $userService->getByUsername($user->username);

        $this->assertEquals($fetchedUser->username, $user->username);
        $this->assertEquals($fetchedUser->firstname, $user->firstname);
        $this->assertEquals($fetchedUser->lastname, $user->lastname);
        $this->assertNotEmpty($fetchedUser->node);
    }

    public function testGetNodeByUsername()
    {
        $this->buildRealClient();
        $this->clearDB();
        $this->createUser();
        $username = $this->standardUser->username;
        $service = new UserService();
        $node = $service->getNodeByUsername($username);
        $this->assertEquals($node->getProperty('username'), $username);
    }

    public function testSearchUserByUsername()
    {
        $this->buildRealClient();
        $this->clearDB();
        $this->createUser();
        $user2 = $this->createUser(true);
        $service = new UserService();
        $foundUsers = $service->searchByUsername($this->standardUser->username, $this->standardUser->username);
        $matchedUser = $foundUsers[0];
        $this->assertEquals($matchedUser->username, $user2->username);

    }

    public function testSuggestions()
    {
        $this->buildRealClient();
        $this->clearDB();
        $this->loadGraph();
        $username = 'bernhard.isidro';
        $service = new UserService();
        $suggestions = $service->friendSuggestions($username);
        foreach ($suggestions as $user) {
            $this->assertTrue($user->username !== $username);
            $this->assertTrue(is_int($user->commonFriends));
        }
    }

    public function testFollowUser()
    {
        $this->buildRealClient();
        $this->clearDB();
        $this->loadGraph();
        $username = 'bernhard.isidro';
        $service = new UserService();
        $suggestions = $service->friendSuggestions($username);
        $toFollow = $suggestions[0];
        $service->followUser($username, $toFollow->username);
        $following = $service->following($username);
        $this->assertTrue($this->checkUserIsFollowed($toFollow->username, $following));
    }

    public function testUnfollowUser()
    {
        $this->buildRealClient();
        $this->clearDB();
        $this->loadGraph();
        $username = 'bernhard.isidro';
        $service = new UserService();
        $following = $service->following($username);
        $toUnfollow = $following[0]->username;
        $service->unfollowUser($username, $toUnfollow);
        $following = $service->following($username);
        $this->assertTrue($this->checkUserIsUnfollowed($toUnfollow, $following));
    }

    public function testGetNodeById()
    {
        $this->buildRealClient();
        $this->clearDB();
        $this->loadGraph();
        $username = 'bernhard.isidro';
        $service = new UserService();
        $user = $service->getByUsername($username);
        $id = $user->node->getId();
        $userById = $service->getByNodeId($id);
        $this->assertEquals($userById, $user);

    }

    private function checkUserIsFollowed($user, array $userMap)
    {
        foreach ($userMap as $following){
            if ($following->username == $user) {
                return true;
            }
        }

        return false;
    }

    private function checkUserIsUnfollowed($user, array $userMap)
    {
        foreach ($userMap as $following){
            if ($following->username == $user) {
                return false;
            }
        }

        return true;
    }

    private function createUser($randomized = false)
    {
        $user = $this->standardUser;
        if ($randomized){
            $user = new User();
            $id = mt_rand(101,200);
            $user->id = $id;
            $user->username = $this->standardUser->username . $id;
            $user->firstname = $this->standardUser->firstname;
            $user->lastname = $this->standardUser->lastname;
        }
        $userService = new UserService();
        $userService->save($user);

        return $user;
    }
}