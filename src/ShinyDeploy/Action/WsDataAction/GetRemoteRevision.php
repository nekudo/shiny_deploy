<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsLogResponder;

class GetRemoteRevision extends WsDataAction
{
    /**
     * Fetches remote repository revision
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['deploymentId'])) {
            throw new WebsocketException('Invalid getLocalRevision request received.');
        }
        $deploymentId = (int)$actionPayload['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);
        $deployment = $deployments->getDeployment($deploymentId);
        $logResponder = new WsLogResponder($this->config, $this->logger);
        $logResponder->setClientId($this->clientId);
        $deployment->setLogResponder($logResponder);
        $revision = $deployment->getRemoteRevision();
        $this->responder->setPayload($revision);
        return true;
    }
}
