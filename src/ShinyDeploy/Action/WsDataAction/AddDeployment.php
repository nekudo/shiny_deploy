<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\InvalidPayloadException;
use Valitron\Validator;

class AddDeployment extends WsDataAction
{
    /**
     * Adds new deployment to database.
     *
     * @param array $actionPayload
     * @return boolean
     * @throws InvalidPayloadException
     */
    public function __invoke(array $actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentData'])) {
            throw new InvalidPayloadException('Invalid addDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        $deployments = new Deployments($this->config, $this->logger);
        if (isset($deploymentData['tasks'])) {
            $deploymentData['tasks'] = $deployments->encodeDeploymentTasks($deploymentData['tasks']);
        }

        // validate input:
        $validator = new Validator($deploymentData);
        $validator->rules($deployments->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // check for other deployment with same target:
        if ($deployments->targetExists($deploymentData) === true) {
            $this->responder->setError('Another deployment is already deploying to this target.');
            return false;
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        // add deployment:
        $deployments->setEnryptionKey($encryptionKey);
        $addResult = $deployments->addDeployment($deploymentData);
        if ($addResult === false) {
            $this->responder->setError('Could not add deployment to database.');
            return false;
        }
        return true;
    }
}
