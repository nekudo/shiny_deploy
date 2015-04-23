<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Responder\WsDataResponder;

class GetDeployments extends WsDataAction
{
    /**
     * Fetches a deployments list
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        $deploymentsDomain = new Deployments($this->config, $this->logger);
        $deployments = $deploymentsDomain->getDeployments();
        $responder = new WsDataResponder($this->config, $this->logger);
        $responder->setPayload($deployments);
        $this->setResponse($responder);
        return true;
    }

    /**
     * Sets responder
     *
     * @param WsDataResponder $responder
     */
    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
