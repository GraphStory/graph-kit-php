<?php

use GraphStory\GraphKit\Exception\JsonResponseEncodingException;
use GraphStory\GraphKit\Model\Content;
use GraphStory\GraphKit\Model\User;
use GraphStory\GraphKit\Neo4jClient;
use GraphStory\GraphKit\Service\ContentService;
use GraphStory\GraphKit\Service\UserService;
use GraphStory\GraphKit\Slim\JsonResponse;
use GraphStory\GraphKit\Slim\Middleware\Navigation;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim\Middleware\SessionCookie;
use Slim\Mustache\Mustache;
use Slim\Slim;

if (getenv('SLIM_MODE') !== 'test') {
    $neo4jClient = new \Everyman\Neo4j\Client(
        $config['graphStory']['restHost'],
        $config['graphStory']['restPort']
    );

    $neo4jClient->getTransport()->setAuth(
        $config['graphStory']['restUsername'],
        $config['graphStory']['restPassword']
    );

    if ($config['graphStory']['https']) {
        $neo4jClient->getTransport()->useHttps();
    }

    // neo client
    Neo4jClient::setClient($neo4jClient);
}

$app = new Slim($config['slim']);

$app->container->singleton('logger', function () use ($config) {
    $logger = new Logger('graph-kit');
    $logger->pushHandler(new StreamHandler(
        $config['logging']['logFile'],
        $config['logging']['logLevel']
    ));

    return $logger;
});

$app->jsonResponse = function () use ($app) {
    return new JsonResponse($app->response);
};

$app->error(function (\Exception $e) use ($app) {
    if ($e instanceof JsonResponseEncodingException) {
        $app->logger->error(
            sprintf("Error encoding JSON response for request path '%'", $app->request->getPathInfo())
        );

        $app->jsonResponse->build(
            array('error' => array('message' => 'Response body could not be parsed as valid JSON')),
            500
        );
        $app->response->finalize();
    }

    $app->logger->alert('UNHANDLED EXCEPTION', array('exception' => $e));

    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        return $app->render('errors/500-authed.mustache');
    }

    $app->render('errors/500-guest.mustache');
});

$app->view(new Mustache());
$app->view->parserOptions = $config['mustache'];
$app->view->appendData(array('copyrightYear' => date('Y')));
$app->view->appendData(array('applicationEnvironment' => SLIM_MODE));

$app->add(new Navigation());
$app->add(new SessionCookie(
    array('expires' => '12 hours')
));

$isLoggedIn = function () use ($app) {
    if (empty($_SESSION['username'])) {
        $app->redirect($app->urlFor('home'));
    }
};

// home
$app->get('/', function () use ($app) {
    $isAuthenticated = (empty($_SESSION['username'])) ? false : true;

    $app->render('home/index.mustache', array(
        'isAuthenticated' => $isAuthenticated,
    ));
})->name('home');

$app->get('/login', function () use ($app) {
    $app->redirect($app->urlFor('home'));
});

// login
$app->post('/login', function () use ($app) {
    $params = $app->request()->post();

    // make sure the user name was passed.
    $username = trim($params['username']);

    if (!empty($username)) {
        // lower case the username.
        $username = strtolower($username);
        $checkuser = UserService::getByUsername($username);

        // match
        if (!is_null($checkuser)) {
            $_SESSION['username'] = $username;

            $app->redirect($app->urlFor('social-graph'));
        } else {
            $app->render('home/message.mustache', array(
                'msg' => 'The username you entered was not found.',
            ));
        }
    } else {
        $app->render('home/message.mustache', array(
            'msg' => 'Please enter a username.',
        ));
    }
});

$app->get('/logout', function () use ($app) {
    unset($_SESSION['username']);
    $app->redirect($app->urlFor('home'));
});

// social - show user form
$app->get('/user', $isLoggedIn, function () use ($app) {
    $user = UserService::getByUsername($_SESSION['username']);
    $app->render('graphs/social/user.mustache', array(
        'user' => $user,
        'userEditUrl' => $app->urlFor('user-edit'),
    ));
})->name('user-profile');

// create new user & redirect
$app->post('/user/add', function () use ($app) {
    $username = $app->request->post('username');

    if ($username) {
        // lower case the username.
        $username = strtolower(trim($username));

        // see if there's already a User node with this username
        $checkuser = UserService::getByUsername($username);

        // No? then save it
        if (is_null($checkuser)) {
            // setup the object
            $user = new User();
            $user->username = $username;
            // save it
            UserService::save($user);

            // Authenticate user
            $_SESSION['username'] = $username;

            $app->flash('joinSuccess', true);
            $app->redirect($app->urlFor('social-graph'));
        } else {
            // show the "try again" message.
            $app->render('home/index.mustache', array(
                'error' => 'The username "' . $username . '" already exists. Please try again.',
            ));
        }
    } else {
        // username field was empty
        $app->render('home/index.mustache', array(
            'error' => 'Please enter a username.',
        ));
    }
});

// social - edit a user
$app->put('/user/edit', function () use ($app) {
    $params = json_decode($app->request->getBody());

    $user = UserService::getByUsername($_SESSION['username']);
    $user->firstname = $params->firstname;
    $user->lastname = $params->lastname;

    UserService::save($user);

    $app->jsonResponse->build($user);
})->name('user-edit');

/********************************
 * Start Social Graph
 *******************************/

// social - friends - get list of friends and search for new ones
$app->get('/friends', $isLoggedIn, function () use ($app) {
    $user = UserService::getByUsername($_SESSION['username']);
    $following = UserService::following($_SESSION['username']);
    $suggestions = UserService::friendSuggestions($_SESSION['username']);

    $app->render('graphs/social/friends.mustache', array(
        'user' => $user,
        'following' => $following,
        'suggestions' => $suggestions,
        'unfollowUrl' => $app->urlFor('social-unfollow', array('userToUnfollow' => null)),
        'followUrl' => $app->urlFor('social-follow', array('userToFollow' => null)),
    ));
})->name('social-friends');

// takes current user session and will follow :username, e.g. one way follow
$app->get('/follow/:userToFollow', function ($userToFollow) use ($app) {
    UserService::followUser($_SESSION['username'], $userToFollow);

    $following = UserService::following($_SESSION['username']);
    $unfollowUrl = $app->urlFor('social-unfollow', array('userToUnfollow' => null));
    $return = array();

    foreach ($following as $friend) {
        $content = array_merge(array('unfollowUrl' => $unfollowUrl), $friend->toArray());

        $return[] = $app->view
            ->getInstance()
            ->render('graphs/social/friends-partial', $content);
    }

    $app->jsonResponse->build(
        array('following' => $return)
    );
})->name('social-follow');

// takes current user session and will unfollow :username
$app->delete('/unfollow/:userToUnfollow', function ($userToUnfollow) use ($app) {
    UserService::unfollowUser($_SESSION['username'], $userToUnfollow);

    $following = UserService::following($_SESSION['username']);
    $unfollowUrl = $app->urlFor('social-unfollow', array('userToUnfollow' => null));
    $return = array();

    foreach ($following as $friend) {
        $content = array_merge(array('unfollowUrl' => $unfollowUrl), $friend->toArray());

        $return[] = $app->view
            ->getInstance()
            ->render('graphs/social/friends-partial', $content);
    }

    $app->jsonResponse->build(
        array('following' => $return)
    );
})->name('social-unfollow');

//search users by name
$app->get('/searchbyusername/:search', function ($search) use ($app) {
    $users = UserService::searchByUsername($search, $_SESSION['username']);

    $app->jsonResponse->build(
        array('users' => $users)
    );
})->name('user-search');

// social - show posts
$app->get('/posts', $isLoggedIn, function () use ($app) {
    $content = ContentService::getContent($_SESSION['username'], 0);
    $socialContent = array_slice($content, 0, 3);
    $moreContent = (count($content) >= 4);

    $app->render('graphs/social/posts.mustache', array(
        'username' => $_SESSION['username'],
        'socialContent' => $socialContent,
        'moreContent' => $moreContent,
        'moreContentUrl' => $app->urlFor('social-feed', array('skip' => null)),
        'addContentUrl' => $app->urlFor('social-post-add'),
        'friendsUrl' => $app->urlFor('social-friends'),
        'postUrl' => $app->urlFor('social-post', array('postId' => null)),
    ));
})->name('social-graph');

// social - return posts as JSON
$app->get('/feed/:skip', $isLoggedIn, function ($skip) use ($app) {
    $result = ContentService::getContent($_SESSION['username'], (int) $skip);

    $postUrl = $app->urlFor('social-post', array('postId' => null));
    $return = array();

    foreach ($result as $content) {
        $content = array_merge(array('postUrl' => $postUrl), $content->toArray());

        $return[] = $app->view
            ->getInstance()
            ->render('graphs/social/posts-partial', $content);
    }

    $app->jsonResponse->build(
        array('content' => $return)
    );
})->name('social-feed');

// social - add a post
$app->post('/posts', function () use ($app) {
    $request = $app->request();
    $contentParams = json_decode($request->getBody());

    $content = new Content();
    $content->title = $contentParams->title;
    $content->url = $contentParams->url;

    // are tags set?
    if (isset($contentParams->tagstr)) {
        $content->tagstr = $contentParams->tagstr;
    }

    $result = ContentService::add($_SESSION['username'], $content);
    $content = $result[0];
    $postUrl = $app->urlFor('social-post', array('postId' => null));
    $content = array_merge(array('postUrl' => $postUrl), $content->toArray());

    $app->render('graphs/social/posts-partial', $content);
})->name('social-post-add');

// social - edit a post
$app->put('/posts', function () use ($app) {
    $request = $app->request();
    $contentParams = json_decode($request->getBody());
    $content = ContentService::getContentById(
        $_SESSION['username'],
        $contentParams->contentId
    );
    $content = $content[0];

    $content->title = $contentParams->title;
    $content->url = $contentParams->url;

    // are tags set?
    if (isset($contentParams->tagstr)) {
        $content->tagstr = $contentParams->tagstr;
    }

    $result = ContentService::edit($content);
    $postUrl = $app->urlFor('social-post', array('postId' => null));
    $content = array_merge(array('postUrl' => $postUrl), $content->toArray());

    $app->render('graphs/social/posts-partial', $content);
})->name('social-post-edit');

// social - remove a post
$app->delete('/posts/:postId', $isLoggedIn, function ($postId) use ($app) {
    ContentService::delete($_SESSION['username'], $postId);
})->name('social-post-delete');

// social - show post
$app->get('/posts/:postId', $isLoggedIn, function ($postId) use ($app) {
    $post = ContentService::getContentById($_SESSION['username'], $postId);
    $content = new Content();

    if (!empty($post)) {
        $content = $post[0];
    }

    $app->render('graphs/social/post.mustache', array(
        'usr' => $_SESSION['username'],
        'postContent' => $content,
    ));
})->name('social-post');
