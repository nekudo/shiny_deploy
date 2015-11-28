<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\WebsocketException;

class UserExists extends WsDataAction
{
    /**
     * Checks if master-password hash was already set.
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        if (empty($actionPayload['username'])) {
            throw new WebsocketException('Username missing.');
        }
        $username = $actionPayload['username'];
        $auth = new Auth($this->config, $this->logger);
        $userExists = $auth->userExists($username);
        $payload = ['userExists' => $userExists];
        $this->responder->setPayload($payload);
        return true;
    }
}
