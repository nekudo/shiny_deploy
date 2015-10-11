<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Repositories;

class GetRepositories extends WsDataAction
{
    /**
     * Fetches a repositories list
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        $repositoriesDomain = new Repositories($this->config, $this->logger);
        $repositories = $repositoriesDomain->getRepositories();
        $this->responder->setPayload($repositories);
        return true;
    }
}
