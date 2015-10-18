<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use Valitron\Validator;

class AddDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['deploymentData'])) {
            throw new WebsocketException('Invalid addDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        if (isset($deploymentData['tasks'])) {
            $deploymentData['tasks'] = json_encode($deploymentData['tasks']);
        }
        $deployments = new Deployments($this->config, $this->logger);

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

        // add deployments:
        $addResult = $deployments->addDeployment($deploymentData);
        if ($addResult === false) {
            $this->responder->setError('Could not add deployment to database.');
            return false;
        }
        return true;
    }
}
