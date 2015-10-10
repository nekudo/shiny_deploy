<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class DeleteServer extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['serverId'])) {
            throw new WebsocketException('Invalid deleteServer request received.');
        }
        $serverId = (int)$actionPayload['serverId'];
        $serversDomain = new Servers($this->config, $this->logger);

        // check if server still in use:
        if ($serversDomain->serverInUse($serverId) === true) {
            $this->responder->setError('This server is still used in a deployment.');
            return false;
        }

        // remove server:
        $addResult = $serversDomain->deleteServer($serverId);
        if ($addResult === false) {
            $this->responder->setError('Could not remove server from database.');
            return false;
        }
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
