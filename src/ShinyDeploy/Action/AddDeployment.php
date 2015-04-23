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
        $deploymentDomain = new Deployments($this->config, $this->logger);

        // validate input:
        $validator = new Validator($deploymentData);
        $validator->rules($deploymentDomain->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // add deployments:
        $addResult = $deploymentDomain->addDeployment($deploymentData);
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
