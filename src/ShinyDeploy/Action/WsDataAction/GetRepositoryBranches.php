<?php
namespace ShinyDeploy\Action\WsDataAction;

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
            // get repository data:
            $repositoryId = (int)$actionPayload['repositoryId'];
            $repositories = new Repositories($this->config, $this->logger);
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
        } catch (\RuntimeException $e) {
            $this->responder->setError('Could not load branches.');
            return false;
        }
    }
}
