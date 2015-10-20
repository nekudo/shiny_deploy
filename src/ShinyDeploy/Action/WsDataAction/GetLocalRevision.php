<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\NullResponder;

class GetLocalRevision extends WsDataAction
{
    /**
     * Fetches a deployments list
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
        $logResponder = new NullResponder($this->config, $this->logger);
        $deployment->setLogResponder($logResponder);
        $revision = $deployment->getLocalRevision();
        $this->responder->setPayload($revision);
        return true;
    }
}
