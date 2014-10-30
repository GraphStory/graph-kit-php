<?php

namespace GraphStory\GraphKit\Tests\Service;

use GraphStory\GraphKit\Tests\GraphKitTestCase;
use GraphStory\GraphKit\Model\User,
    GraphStory\GraphKit\Service\UserService;

class UserServiceTest extends GraphKitTestCase
{
    public function testCreateUser()
    {
        $this->buildRealClient();
        $user = new User();
        $user->id = mt_rand(1, 100);
        $user->username = 'serialgrapher'.$user->id;
        $user->firstname = 'Roger';
        $user->lastname = 'McCarthy';
        $userService = new UserService();
        $userService->save($user);

        $fetchedUser = $userService->getByUsername($user->username);
        $this->assertEquals($fetchedUser->username, $user->username);
        $this->assertEquals($fetchedUser->firstname, $user->firstname);
        $this->assertEquals($fetchedUser->lastname, $user->lastname);
        $this->assertNotEmpty($fetchedUser->node);
    }
}