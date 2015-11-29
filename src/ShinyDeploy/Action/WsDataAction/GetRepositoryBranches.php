<?php
namespace ShinyDeploy\Action\WsDataAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;

class GetRepositoryBranches extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['repositoryId'])) {
            throw new WebsocketException('Invalid getRepositoryBranches request received.');
        }

        try {
            // get users encryption key:
            $auth = new Auth($this->config, $this->logger);
            $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
            if (empty($encryptionKey)) {
                $this->responder->setError('Could not get encryption key.');
                return false;
            }

            // get repository data:
            $repositoryId = (int)$actionPayload['repositoryId'];
            $repositories = new Repositories($this->config, $this->logger);
            $repositories->setEnryptionKey($encryptionKey);
            $repository = $repositories->getRepository($repositoryId);
            if (empty($repository)) {
                $this->responder->setError('Repository not found in database.');
                return false;
            }

            if ($repository->checkConnectivity() === false) {
                $this->responder->setError('Repository not reachable. Check URL and credentials.');
                return false;
            }

            // get repository branches:
            $branches = $repository->getBranches();
            if (empty($branches)) {
                $this->responder->setError('Could not load branches.');
                return false;
            }

            $this->responder->setPayload($branches);
            return true;
        } catch (RuntimeException $e) {
            $this->responder->setError('Could not load branches.');
            return false;
        }
    }
}
