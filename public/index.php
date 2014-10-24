<?php

if (!defined('SLIM_MODE')) {
    $mode = getenv('SLIM_MODE') ? getenv('SLIM_MODE') : 'production';
    define('SLIM_MODE', $mode);
}

define('APPLICATION_PATH', realpath(dirname(__DIR__)));

require_once APPLICATION_PATH . '/vendor/autoload.php';

use GraphStory\GraphKit\Exception\JsonResponseEncodingException;
use GraphStory\GraphKit\Neo4jClient;
use GraphStory\GraphKit\Service\Content;
use GraphStory\GraphKit\Service\User;
use GraphStory\GraphKit\Slim\JsonResponse;
use GraphStory\GraphKit\Slim\Middleware\Navigation;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim\Middleware\SessionCookie;
use Slim\Mustache\Mustache;
use Slim\Slim;
use Zend\Config\Factory as ConfigFactory;

$configPaths = sprintf(
    '%s/config/{,*.}{global,%s,secret}.php',
    APPLICATION_PATH,
    SLIM_MODE
);

$config = ConfigFactory::fromFiles(glob($configPaths, GLOB_BRACE));

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

$app = new Slim($config['slim']);

$app->container->singleton('logger', function () use ($config) {
    $logger = new Logger('slim-skeleton');
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

// neo client
Neo4jClient::setClient($neo4jClient);

$app->view(new Mustache());
$app->view->parserOptions = $config['mustache'];
$app->view->appendData(array('copyrightYear' => date('Y')));

$app->add(new Navigation());
$app->add(new SessionCookie());

$isLoggedIn = function () use ($app) {
    if (!isset($_SESSION['username']) && empty($_SESSION['username'])) {
        $app->redirect($app->urlFor('home'));
    }
};

// home
$app->get('/', function () use ($app) {
    $app->render('home/index.mustache');
})->name('home');

// create new user & redirect
$app->post('/user/add', function () use ($app) {
    $username = $app->request->post('username');

    if ($username) {
        // lower case the username.
        $username = strtolower(trim($username));

        // see if there's already a User node with this username
        $checkuser = User::getByUsername($username);

        // No? then save it
        if (is_null($checkuser)) {
            // setup the object
            $user = new User();
            $user->username = $username;
            // save it
            User::save($user);

            // Authenticate user
            $_SESSION['username'] = $username;

            $app->flash('joinSuccess', true);
            $app->redirect($app->urlFor('social-graph'));
        } else {
            // show the "try again" message.
            $app->render('home/index.mustache', array(
                'error' => 'The username "'.$username.'" already exists. Please try again.',
            ));
        }
    } else {
        // username field was empty
        $app->render('home/index.mustache', array(
            'error' => 'Please enter a username.',
        ));
    }
});

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
        $checkuser = User::getByUsername($username);

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

/********************************
start Social Graph
********************************/

// social - show user form
$app->get('/user', $isLoggedIn, function () use ($app) {
    $user = User::getByUsername($_SESSION['username']);
    $app->render('graphs/social/user.mustache', array('user' => $user));
});

// social - edit a user
$app->put('/user/edit', function () use ($app) {
    $request = $app->request();
    $usrparams = json_decode($request->getBody());

    $user = User::getByUsername($_SESSION['username']);
    $user->firstname = $usrparams->firstname;
    $user->lastname = $usrparams->lastname;
    User::save($user);

    $app->jsonResponse->build($user);
});

// social - friends - get list of friends and search for new ones
$app->get('/friends', $isLoggedIn, function () use ($app) {
    $user = User::getByUsername($_SESSION['username']);
    $following = User::following($_SESSION['username']);

    $app->render('graphs/social/friends.mustache', array(
        'user' => $user,
        'following' => $following,
    ));
});

// takes current user session and will follow :username, e.g. one way follow
$app->get('/follow/:username', function ($username) use ($app) {
    $rel = User::followUser($_SESSION['username'], $username);
    $following =  User::following($_SESSION['username']);

    $app->jsonResponse->build(
        array('following' => $following)
    );
});

// takes current user session and will follow :username, e.g. one way follow
$app->get('/unfollow/:username', function ($username) use ($app) {
    $rel = User::unfollowUser($_SESSION['username'], $username, $app);
    $following =  User::following($_SESSION['username']);

    $app->jsonResponse->build(
        array('following' => $following)
    );
});

//search users by name
$app->get('/searchbyusername/:u', function ($u) use ($app) {
    $users = User::searchByUsername($u, $_SESSION['username']);

    $app->jsonResponse->build(
        array('users' => $users)
    );
});

// social - show posts
$app->get('/posts', $isLoggedIn, function () use ($app) {
    $content = Content::getContent($_SESSION['username'], 0);
    $socialContent = array_slice($content, 0, 3);
    $moreContent = (count($content) >= 4);

    $app->render('graphs/social/posts.mustache', array(
        'usr' => $_SESSION['username'],
        'socialContent' => $socialContent,
        'moreContent' => $moreContent,
    ));
})->name('social-graph');

// social - return posts via JSON
$app->get('/postsfeed/:s', $isLoggedIn, function ($s) use ($app) {
    $content =  Content::getContent($_SESSION['username'], (int) $s);
    $app->jsonResponse->build(
        array('content' => $content)
    );
});

// social - show post
$app->get('/viewpost/:postId', $isLoggedIn, function ($postId) use ($app) {
    $post =  Content::getContentItemByUUID($_SESSION['username'], $postId);
    $content = new Content();

    if (!empty($post)) {
        $content = $post[0];
    }

    $app->render('graphs/social/post.mustache', array(
        'usr' => $_SESSION['username'],
        'postContent' => $content,
    ));
});

// social - add a post
$app->post('/posts/add', function () use ($app) {
    $request = $app->request();
    $body = $request->getBody();
    $contentParams = json_decode($request->getBody());
    $content = new Content();
    $content->title = $contentParams->title;
    $content->url = $contentParams->url;
    // are tags set?
    if (isset($contentParams->tagstr)) {
        $content->tagstr = $contentParams->tagstr;
    }
    $post =  Content::add($_SESSION['username'], $content);

    $app->jsonResponse->build(
        array('content' => $post)
    );
});

// social - edit a post
$app->post('/posts/edit', function () use ($app) {
    // Not implemented
});

// social - remove a post
$app->get('/posts/remove/:postId', function ($postId) use ($app) {

});

/********************************
start Interest Graph
********************************/
// add tags to owned post
$app->post('/posts/addwithtags', function () use ($app) {
    // Not implemented
});

//filter based on my owned posts and specific tag

// add current user tags to non-owned post
$app->get('/mytags/add/:tags', function ($tags) use ($app) {
    $app->render('graphs/social/posts.mustache');
});

//filter based on my non-owned posts and specific tag


/********************************
start Consumption Graph
********************************/
// add new product or content with tag

// TODO function to add consumption

// show content trail (last 3)

// filter users who used the same tag as a specific product has used

$app->get('/posts/:postId', function ($postId) use ($app) {
    $app->render('graphs/social/posts.mustache');
});

/********************************
start Location Graph
********************************/

// add stores with lat/long
$app->get('/social', function () use ($app) {
    $app->render('graphs/social/posts.mustache');
});

// add products to stores

// search for stores nearby

// search for products nearby

// filter for users who have searched for products and have same tags as products

// suggest user to store

/********************************
start Intent Graph
********************************/
// social - show posts
$app->get('/social', function () use ($app) {
    $app->render('graphs/social/posts.mustache');
});

$app->get('/user/:id', function ($id) use ($app) {
    $user = User::getByNodeId($id);
    $body = 'sup world';
    $app->render('index.mustache', array(
        'title' => $user->name,
        'body' => $body
    ));
    /*
    foreach ($user as $obj) {
        echo $$obj->name;
    }
     */
    // social is user following user
    // interest is tags
    // consumption is content or products viewed
    // location is location
    // intent variables are (1) frequency of tags (2) product rating (3) content rating (4) location frequency and (5) intersection of tags to consumption items
});

// Run app
$app->run();
