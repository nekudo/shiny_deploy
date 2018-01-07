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
     * @return bool
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
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
