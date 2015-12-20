<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\InvalidPayloadException;

class GetDeploymentData extends WsDataAction
{
    /**
     * Fetches deploymant data from database.
     * 
     * @param array $actionPayload
     * @return boolean
     * @throws InvalidPayloadException
     */
    public function __invoke(array $actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentId'])) {
            throw new InvalidPayloadException('Invalid getDeploymentData request received.');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        $deploymentId = (int)$actionPayload['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deploymentData = $deployments->getDeploymentData($deploymentId);
        if (empty($deploymentData)) {
            $this->responder->setError('Deployment not found in database.');
            return false;
        }
        $this->responder->setPayload($deploymentData);
        return true;
    }
}
