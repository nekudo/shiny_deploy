<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Servers;

class GetServers extends WsDataAction
{
    /**
     * Fetches a server list.
     *
     * @param array $actionPayload
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     * @return bool
     */
    public function __invoke(array $actionPayload) : bool
    {
        $this->authorize($this->clientId);

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        $serversDomain = new Servers($this->config, $this->logger);
        $serversDomain->setEnryptionKey($encryptionKey);
        $servers = $serversDomain->getServers();
        $this->responder->setPayload($servers);
        return true;
    }
}
