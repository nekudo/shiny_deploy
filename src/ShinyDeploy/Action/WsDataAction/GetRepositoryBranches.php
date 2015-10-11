<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Exceptions\WebsocketException;

class GetRepositoryBranches extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['repositoryId'])) {
            throw new WebsocketException('Invalid getRepositoryBranches request received.');
        }

        // get repository data:
        $repositoryId = (int)$actionPayload['repositoryId'];
        $repositoriesDomain = new Repositories($this->config, $this->logger);
        $repositoryData = $repositoriesDomain->getRepositoryData($repositoryId);
        if (empty($repositoryData)) {
            $this->responder->setError('Repository not found in database.');
            return false;
        }

        if ($repositoriesDomain->checkUrl($repositoryData) === false) {
            $this->responder->setError('Repository not reachable. Check URL and credentials.');
            return false;
        }

        // get repository branches:
        try {
            $repositoryDomain = new Repository($this->config, $this->logger);
            $gitDomain = new Git($this->config, $this->logger);
            $repoPath = $repositoryDomain->getLocalPath($repositoryData['url']);
            $branches = $gitDomain->getRemoteBranches($repoPath);
            $this->responder->setPayload($branches);
            return true;
        } catch (\RuntimeException $e) {
            $this->responder->setError('Could not load branches.');
            return false;
        }
    }
}
