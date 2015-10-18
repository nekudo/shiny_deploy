<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Deployments;

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
        $deployments = new Deployments($this->config, $this->logger);
        $deployments = $deployments->getDeployments();
        $this->responder->setPayload($deployments);
        return true;
    }
}
