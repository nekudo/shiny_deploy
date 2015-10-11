<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\WebsocketException;

class DeleteServer extends WsDataAction
{
    public function __invoke($actionPayload)
    {
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
}
