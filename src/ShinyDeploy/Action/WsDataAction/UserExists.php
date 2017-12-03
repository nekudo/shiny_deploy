<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\MissingDataException;

class UserExists extends WsDataAction
{
    /**
     * Checks if master-password hash was already set.
     *
     * @param array $actionPayload
     * @return bool
     * @throws MissingDataException
     */
    public function __invoke(array $actionPayload) : bool
    {
        if (empty($actionPayload['username'])) {
            throw new MissingDataException('Username missing.');
        }
        $username = $actionPayload['username'];
        $auth = new Auth($this->config, $this->logger);
        $userExists = $auth->userExists($username);
        $payload = ['userExists' => $userExists];
        $this->responder->setPayload($payload);
        return true;
    }
}
