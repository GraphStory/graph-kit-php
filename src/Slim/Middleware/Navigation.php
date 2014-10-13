<?php

namespace GraphStory\GraphKit\Slim\Middleware;

use Slim\Middleware;

/**
 * Navigation Middleware
 *
 * Constructs array of navigation items and appends them to the view. 
 */
class Navigation extends Middleware
{
    /**
     * Constructs array of navigation items and appends them to the view. Navigation
     * items differ if user is authenticated or not.
     */
    public function call()
    {
        $app = $this->app;

        $navigation = [
            ['caption' => 'Social Graph', 'href' => $app->urlFor('social-graph')],
            ['caption' => 'Interest Graph', 'href' => '#'],
            ['caption' => 'Consumption Graph', 'href' => '#'],
            ['caption' => 'Location Graph', 'href' => '#'],
            ['caption' => 'Intent Graph', 'href' => '#'],
        ];

        if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
            $navigation[] = ['caption' => 'Logout', 'href' => '/logout'];
        }

        $socialNavigation = [
            ['caption' => 'Social Feed', 'href' => $app->urlFor('social-graph')],
            ['caption' => 'User Settings', 'href' => '/user'],
            ['caption' => 'Friends', 'href' => '/friends'],
        ];

        foreach ($navigation as &$link) {
            if ($link['href'] == $this->app->request()->getPath()) {
                $link['class'] = 'active';
            } else {
                $link['class'] = '';
            }
        }

        $this->app->view()->appendData([
            'navigation' => $navigation,
            'socialNavigation' => $socialNavigation,
        ]);

        $this->next->call();
    }
}
