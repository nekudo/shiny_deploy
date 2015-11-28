<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\WebsocketException;

class GetServerData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['serverId'])) {
            throw new WebsocketException('Invalid getServerData request received.');
        }
        $serverId = (int)$actionPayload['serverId'];
        $servers = new Servers($this->config, $this->logger);

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        $servers->setEnryptionKey($encryptionKey);
        $serverData = $servers->getServerData($serverId);
        if (empty($serverData)) {
            $this->responder->setError('Server not found in database.');
            return false;
        }
        $this->responder->setPayload($serverData);
        return true;
    }
}
