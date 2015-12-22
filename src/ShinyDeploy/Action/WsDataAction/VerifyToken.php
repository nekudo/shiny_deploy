<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class VerifyToken extends WsDataAction
{
    /**
     * Fetches a server list
     * @param array $actionPayload
     * @return bool
     */
    public function __invoke(array $actionPayload)
    {
        if (empty($actionPayload['token'])) {
            $this->responder->setError('Token not provided.');
            return false;
        }
        $auth = new Auth($this->config, $this->logger);
        $tokenIsValid = $auth->validateToken($actionPayload['token'], $this->clientId);
        if ($tokenIsValid !== true) {
            $this->responder->setError('Invalid token.');
        }

        $this->responder->setPayload(['success' => true]);
    }
}
