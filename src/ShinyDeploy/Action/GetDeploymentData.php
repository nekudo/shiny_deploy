<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class GetDeploymentData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['deploymentId'])) {
            throw new WebsocketException('Invalid getDeploymentData request received.');
        }
        $deploymentId = (int)$actionPayload['deploymentId'];
        $deploymentsDomain = new Deployments($this->config, $this->logger);
        $deploymentData = $deploymentsDomain->getDeploymentData($deploymentId);
        if (empty($deploymentData)) {
            $this->responder->setError('Deployment not found in database.');
            return false;
        }
        $this->responder->setPayload($deploymentData);
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
