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
     * @return bool
     */
    public function log(string $message): bool
    {
        return true;
    }

    /**
     * Sends a log message of type "success".
     *
     * @param string $message
     * @return bool
     */
    public function success(string $message): bool
    {
        return true;
    }

    /**
     * Sends a log message of type "info".
     *
     * @param string $message
     * @return bool
     */
    public function info(string $message): bool
    {
        return true;
    }

    /**
     * Sends a log message of type "danger".
     *
     * @param string $message
     * @return bool
     */
    public function danger(string $message): bool
    {
        return true;
    }

    /**
     * Sends a log message of type "error".
     *
     * @param string $message
     * @return bool
     */
    public function error(string $message): bool
    {
        return true;
    }
}
