<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Repositories;

class GetRepositories extends WsDataAction
{
    /**
     * Fetches a repositories list
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

        $repositories = new Repositories($this->config, $this->logger);
        $repositories->setEnryptionKey($encryptionKey);
        $repositoriesData = $repositories->getRepositories();
        $this->responder->setPayload($repositoriesData);
        return true;
    }
}
