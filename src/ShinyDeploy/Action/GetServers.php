<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Responder\WsDataResponder;

class GetServers extends WsDataAction
{
    /**
     * Fetches a server list
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        $serversDomain = new Servers($this->config, $this->logger);
        $servers = $serversDomain->getServers();
        $responder = new WsDataResponder($this->config, $this->logger);
        $responder->setPayload($servers);
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
