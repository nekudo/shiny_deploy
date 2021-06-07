<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class Login extends WsDataAction
{
    /**
     * Does user login and returns JWT.
     *
     * @param array $actionPayload
     * @return bool
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     */
    public function __invoke(array $actionPayload): bool
    {
        if (empty($actionPayload['username'])) {
            $this->responder->setError('Invalid username or password.');
            return false;
        }
        if (empty($actionPayload['password'])) {
            $this->responder->setError('Invalid username or password.');
            return false;
        }

        $auth = new Auth($this->config, $this->logger);
        $inputHash = hash('sha256', $actionPayload['password']);
        $storedHash = $auth->getPasswordHashByUsername($actionPayload['username']);
        if (empty($storedHash)) {
            $this->responder->setError('Invalid username or password.');
            return false;
        }
        if ($inputHash !== $storedHash) {
            $this->responder->setError('Invalid username or password.');
            return false;
        }

        $userKey = $auth->getUserKeyByUsername($actionPayload['username'], $actionPayload['password']);
        $jwt = $auth->generateToken($actionPayload['username'], $userKey, $this->clientId);
        if (empty($jwt)) {
            $this->responder->setError('Error during login. Please check logs.');
            return false;
        }
        $this->responder->setPayload(
            [
                'success' => true,
                'token' => $jwt
            ]
        );
        return true;
    }
}
