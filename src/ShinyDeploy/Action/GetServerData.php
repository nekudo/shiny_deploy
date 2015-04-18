<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class GetServerData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['serverId'])) {
            throw new WebsocketException('Invalid getServerData request received.');
        }
        $serverId = (int)$actionPayload['serverId'];
        $serversDomain = new Servers($this->config, $this->logger);
        $serverData = $serversDomain->getServerData($serverId);
        if (empty($serverData)) {
            $this->responder->setError('Server not found in database.');
            return false;
        }
        $this->responder->setPayload($serverData);
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
