<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class Login extends WsDataAction
{
    /**
     * Fetches a server list
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        if (empty($actionPayload['password'])) {
            $this->responder->setError('Invalid password.');
            return false;
        }
        $auth = new Auth($this->config, $this->logger);
        $inputHash = hash('sha256', $actionPayload['password']);
        $storedHash = $auth->getMasterHash();
        if (empty($storedHash)) {
            $this->responder->setError('No master password set.');
            return false;
        }
        if ($inputHash !== $storedHash) {
            $this->responder->setError('Invalid password.');
            return false;
        }

        $jwt = $auth->generateToken($actionPayload['password'], $this->clientId);
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
