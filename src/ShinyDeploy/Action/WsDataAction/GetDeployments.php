<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;

class GetDeployments extends WsDataAction
{
    /**
     * Fetches a deployments list
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
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
        $deployments = $deployments->getDeployments();
        $this->responder->setPayload($deployments);
        return true;
    }
}
