<?php namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\Responder;

class RestApiResponder extends Responder
{
    /**
     * Responds with a "bad request" header.
     *
     * @param string $errorMessage
     * @return void
     */
    public function respondBadRequest(string $errorMessage = '') : void
    {
        http_response_code(400);
        echo $errorMessage;
        exit;
    }

    /**
     * Responds with an "internal server error" header.
     *
     * @param string $errorMessage
     * @return void
     */
    public function respondError(string $errorMessage = '') : void
    {
        http_response_code(500);
        echo $errorMessage;
        exit;
    }

    /**
     * Responds with "200 OK" header.
     *
     * @param string $message
     * @return void
     */
    public function respond(string $message = '') : void
    {
        http_response_code(200);
        echo $message;
        exit;
    }
}
