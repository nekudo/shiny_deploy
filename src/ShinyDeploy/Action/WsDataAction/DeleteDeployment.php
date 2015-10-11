<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;

class DeleteDeployment extends WsDataAction
{
    public function __invoke($actionPayload)
    {
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
}
