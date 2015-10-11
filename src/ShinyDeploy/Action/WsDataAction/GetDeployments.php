<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Deployments;

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
        $this->responder->setPayload($deployments);
        return true;
    }
}
