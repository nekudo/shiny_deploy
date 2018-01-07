<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class CreateUser extends WsDataAction
{

    /**
     * Saves a new user to database.
     *
     * @param array $actionPayload
     * @return bool
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     */
    public function __invoke(array $actionPayload) : bool
    {
        if (empty($actionPayload['username'])) {
            $this->responder->setError('Username can not be empty.');
            return false;
        }
        if (empty($actionPayload['password']) || empty($actionPayload['password_verify'])) {
            $this->responder->setError('Password can not be empty.');
            return false;
        }
        if ($actionPayload['password'] !== $actionPayload['password_verify']) {
            $this->responder->setError('Passwords do not match.');
            return false;
        }

        $auth = new Auth($this->config, $this->logger);

        // store hash of master password in database:
        if ($actionPayload['username'] === 'system') {
            $result = $auth->createSystemUser($actionPayload['password']);
        } else {
            $result = $auth->createUser($actionPayload['username'], $actionPayload['password']);
        }
        if ($result === false) {
            $this->responder->setError('Could not save user to database.');
            return false;
        }

        // return success
        $this->responder->setPayload(['success' => true]);
        return true;
    }
}
