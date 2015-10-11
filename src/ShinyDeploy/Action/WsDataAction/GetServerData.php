<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Exceptions\WebsocketException;

class GetServerData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
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
}
