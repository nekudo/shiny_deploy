<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Responder\WsDataResponder;

class AddServer extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $serversDomain = new Servers($this->config, $this->logger);
        //$servers = $serversDomain->getServers();
        $responder = new WsDataResponder($this->config, $this->logger);
        $responder->setError('testing error response...');
        $this->setResponse($responder);
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
