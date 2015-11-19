<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;

class GetDeploymentData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['deploymentId'])) {
            throw new WebsocketException('Invalid getDeploymentData request received.');
        }
        $deploymentId = (int)$actionPayload['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);
        $deploymentData = $deployments->getDeploymentData($deploymentId);
        if (empty($deploymentData)) {
            $this->responder->setError('Deployment not found in database.');
            return false;
        }
        $this->responder->setPayload($deploymentData);
        return true;
    }
}
