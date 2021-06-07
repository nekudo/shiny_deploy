<?php
namespace ShinyDeploy\Action\WsDataAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\GitException;
use ShinyDeploy\Exceptions\InvalidPayloadException;

class GetRepositoryBranches extends WsDataAction
{
    /**
     * Fetches list of repository branches.
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

        if (!isset($actionPayload['repositoryId'])) {
            throw new InvalidPayloadException('Invalid getRepositoryBranches request received.');
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
            try {
                $branches = $repository->getBranches();
            } catch (GitException $e) {
                $this->logger->error('Could not fetch git branches: ' . $e->getMessage());
            }
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
