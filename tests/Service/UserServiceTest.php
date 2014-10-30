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

    private function createUser()
    {
        $user = $this->standardUser;
        $userService = new UserService();
        $userService->save($user);
    }
}