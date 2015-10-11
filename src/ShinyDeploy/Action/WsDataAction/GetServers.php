<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Servers;

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
        $this->responder->setPayload($servers);
        return true;
    }
}
