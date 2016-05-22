<?php

/**
 * Graph Kit - PHP
 *
 * @copyright 2014 Graph Story, Inc.
 * @license MIT
 * @link https://github.com/GraphStory/graph-kit-php
 */

namespace GraphStory\GraphKit\Slim;

use GraphStory\GraphKit\Exception\JsonResponseEncodingException;
use Slim\Http\Response;

/**
 * Helper class that wraps Slim\Http\Response and creates a JSON response
 */
class JsonResponse
{
    /**
     * @var Response Slim response
     */
    protected $response;

    /**
     * Public constructor
     *
     * @param Response $response Slim response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Builds JSON response
     *
     * @param  mixed                         $body   Body to be JSON encoded. Can be any type except a resource
     * @param  int                           $status HTTP status
     * @throws JsonResponseEncodingException when json_response fails
     */
    public function build($body, $status = 200)
    {
        $json = !is_string($body) ? json_encode($body) : $body;

        if ($json === false) {
            throw new JsonResponseEncodingException(
                sprintf('Error JSON encoding $body: %s', json_last_error_msg())
            );
        }

        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->write($json);
        $this->response->setStatus($status);
    }
}
