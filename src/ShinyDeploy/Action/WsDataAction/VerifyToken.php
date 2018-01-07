<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class VerifyToken extends WsDataAction
{
    /**
     * Validates an auth-token.
     *
     * @param array $actionPayload
     * @return bool
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     */
    public function __invoke(array $actionPayload) : bool
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
