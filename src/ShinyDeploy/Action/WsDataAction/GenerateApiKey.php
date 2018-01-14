<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\ApiKeys;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\InvalidPayloadException;
use ShinyDeploy\Exceptions\MissingDataException;

class GenerateApiKey extends WsDataAction
{

    /**
     * Generates a new API key and stores it to database.
     *
     * @param array $actionPayload
     * @return bool
     * @throws InvalidPayloadException
     * @throws MissingDataException
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     */
    public function __invoke(array $actionPayload) : bool
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentData'])) {
            throw new InvalidPayloadException('Invalid updateDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        if (empty($deploymentData['id'])) {
            throw new MissingDataException('Deployment id can not be empty.');
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
        $apiKeys->deleteApiKeysByDeploymentId($deploymentData['id']);
        $apiKeyData = $apiKeys->addApiKey($deploymentData['id']);
        $this->responder->setPayload($apiKeyData);
        return true;
    }
}
