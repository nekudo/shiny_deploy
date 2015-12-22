<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\Responder;

/**
 * A dummy responder in cases no logging e.g. is needed.
 */
class NullResponder extends Responder
{
    /**
     * Sends a log message of type "default".
     *
     * @param string $message
     */
    public function log($message)
    {
        return true;
    }

    /**
     * Sends a log message of type "success".
     *
     * @param string $message
     */
    public function success($message)
    {
        return true;
    }

    /**
     * Sends a log message of type "info".
     *
     * @param string $message
     */
    public function info($message)
    {
        return true;
    }

    /**
     * Sends a log message of type "danger".
     *
     * @param string $message
     */
    public function danger($message)
    {
        return true;
    }

    /**
     * Sends a log message of type "error".
     *
     * @param string $message
     * @param string $source
     */
    public function error($message)
    {
        return true;
    }
}
