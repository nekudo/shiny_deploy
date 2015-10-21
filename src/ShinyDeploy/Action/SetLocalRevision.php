<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Responder\NullResponder;
use ShinyDeploy\Responder\WsSetLocalRevisionResponder;

class SetLocalRevision extends Action
{
    /**
     * Fetches a deployments list
     *
     * @param mixed $params
     * @return bool
     */
    public function __invoke($params)
    {
       if (!isset($params['deploymentId'])) {
            throw new RuntimeException('Required parameter missing.');
        }
        $deploymentId = (int)$params['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);
        $deployment = $deployments->getDeployment($deploymentId);
        $logResponder = new NullResponder($this->config, $this->logger);
        $deployment->setLogResponder($logResponder);
        $revision = $deployment->getLocalRevision();
        $responder = new WsSetLocalRevisionResponder($this->config, $this->logger);
        $responder->setClientId($params['clientId']);
        $responder->respond($revision);
        return true;
    }
}
