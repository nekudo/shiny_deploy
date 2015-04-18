<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Responder\WsDataResponder;

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
        $responder = new WsDataResponder($this->config, $this->logger);
        $responder->setPayload($repositories);
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
