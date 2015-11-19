<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;

class DeleteDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);
        
        if (!isset($actionPayload['deploymentId'])) {
            throw new WebsocketException('Invalid deleteDeployment request received.');
        }
        $deploymentId = (int)$actionPayload['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);

        // remove server:
        $addResult = $deployments->deleteDeployment($deploymentId);
        if ($addResult === false) {
            $this->responder->setError('Could not remove deployment from database.');
            return false;
        }
        return true;
    }
}
