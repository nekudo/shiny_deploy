<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\InvalidPayloadException;
use Valitron\Validator;

class UpdateDeployment extends WsDataAction
{
    /**
     * Updates deployment data in database.
     *
     * @param array $actionPayload
     * @throws InvalidPayloadException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     * @return bool
     */
    public function __invoke(array $actionPayload) : bool
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentData'])) {
            throw new InvalidPayloadException('Invalid updateDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        $deployments = new Deployments($this->config, $this->logger);
        if (isset($deploymentData['tasks'])) {
            $deploymentData['tasks'] = $deployments->encodeDeploymentTasks($deploymentData['tasks']);
        }

        // validate input:
        $validator = new Validator($deploymentData);
        $validator->rules($deployments->getUpdateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        // update deployment:
        $deployments->setEnryptionKey($encryptionKey);
        $updateResult = $deployments->updateDeployment($deploymentData);
        if ($updateResult === false) {
            $this->responder->setError('Could not update deployment.');
            return false;
        }
        return true;
    }
}
