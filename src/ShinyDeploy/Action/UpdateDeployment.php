<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;
use Valitron\Validator;

class UpdateDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['deploymentData'])) {
            throw new WebsocketException('Invalid updateDeployment request received.');
        }
        $deploymentData = $actionPayload['deploymentData'];
        $deploymentsDomain = new Deployments($this->config, $this->logger);

        // validate input:
        $validator = new Validator($deploymentData);
        $validator->rules($deploymentsDomain->getUpdateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // update deployment:
        $updateResult = $deploymentsDomain->updateDeployment($deploymentData);
        if ($updateResult === false) {
            $this->responder->setError('Could not update deployment.');
            return false;
        }
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
