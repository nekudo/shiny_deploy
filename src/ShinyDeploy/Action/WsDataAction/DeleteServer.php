<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\InvalidPayloadException;

class DeleteServer extends WsDataAction
{
    /**
     * Removes a server from database.
     * 
     * @param array $actionPayload
     * @return boolean
     * @throws InvalidPayloadException
     */
    public function __invoke(array $actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['serverId'])) {
            throw new InvalidPayloadException('Invalid deleteServer request received.');
        }
        $serverId = (int)$actionPayload['serverId'];
        $servers = new Servers($this->config, $this->logger);

        // check if server still in use:
        if ($servers->serverInUse($serverId) === true) {
            $this->responder->setError('This server is still used in a deployment.');
            return false;
        }

        // remove server:
        $addResult = $servers->deleteServer($serverId);
        if ($addResult === false) {
            $this->responder->setError('Could not remove server from database.');
            return false;
        }
        return true;
    }
}
