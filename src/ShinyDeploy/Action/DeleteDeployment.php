<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class DeleteDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['deploymentId'])) {
            throw new WebsocketException('Invalid deleteDeployment request received.');
        }
        $deploymentId = (int)$actionPayload['deploymentId'];
        $deploymentsDomain = new Deployments($this->config, $this->logger);

        // remove server:
        $addResult = $deploymentsDomain->deleteDeployment($deploymentId);
        if ($addResult === false) {
            $this->responder->setError('Could not remove deployment from database.');
            return false;
        }
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
