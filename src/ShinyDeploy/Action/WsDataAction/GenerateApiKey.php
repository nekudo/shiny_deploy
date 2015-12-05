<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Action\WsDataAction\WsDataAction;
use ShinyDeploy\Domain\Database\ApiKeys;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\WebsocketException;

class GenerateApiKey extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentData'])) {
            throw new WebsocketException('Invalid updateDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        if (empty($deploymentData['id'])) {
            throw new WebsocketException('Deployment id can not be empty.');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        // generate API key:
        $apiKeys = new ApiKeys($this->config, $this->logger);
        $apiKeys->setEnryptionKey($encryptionKey);
        $apiKeyData = $apiKeys->addApiKey($deploymentData['id']);
        $this->responder->setPayload($apiKeyData);
        return true;
    }
}
