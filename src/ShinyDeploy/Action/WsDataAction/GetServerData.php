<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\InvalidPayloadException;

class GetServerData extends WsDataAction
{
    /**
     * Fetches server data from database.
     *
     * @param array $actionPayload
     * @return bool
     * @throws InvalidPayloadException
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     */
    public function __invoke(array $actionPayload): bool
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['serverId'])) {
            throw new InvalidPayloadException('Invalid getServerData request received.');
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
