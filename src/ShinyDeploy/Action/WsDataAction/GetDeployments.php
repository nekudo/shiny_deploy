<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;

class GetDeployments extends WsDataAction
{
    /**
     * Fetches a deployments list
     *
     * @param array $actionPayload
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     * @return bool
     */
    public function __invoke(array $actionPayload) : bool
    {
        $this->authorize($this->clientId);

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deploymentsData = $deployments->getDeployments();
        $this->responder->setPayload($deploymentsData);
        return true;
    }
}
