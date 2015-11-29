<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use Valitron\Validator;

class UpdateDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentData'])) {
            throw new WebsocketException('Invalid updateDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        if (isset($deploymentData['tasks'])) {
            $deploymentData['tasks'] = json_encode($deploymentData['tasks']);
        }
        $deployments = new Deployments($this->config, $this->logger);

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
