<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;
use Valitron\Validator;

class AddDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['deploymentData'])) {
            throw new WebsocketException('Invalid addDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        if (isset($deploymentData['tasks'])) {
            $deploymentData['tasks'] = json_encode($deploymentData['tasks']);
        }
        $deploymentsDomain = new Deployments($this->config, $this->logger);

        // validate input:
        $validator = new Validator($deploymentData);
        $validator->rules($deploymentsDomain->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // check for other deployment with same target:
        if ($deploymentsDomain->targetExists($deploymentData) === true) {
            $this->responder->setError('Another deployment is already deploying to this target.');
            return false;
        }

        // add deployments:
        $addResult = $deploymentsDomain->addDeployment($deploymentData);
        if ($addResult === false) {
            $this->responder->setError('Could not add deployment to database.');
            return false;
        }
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
