<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;

class GetRepositoryData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['repositoryId'])) {
            throw new WebsocketException('Invalid getRepositoryData request received.');
        }
        $repositoryId = (int)$actionPayload['repositoryId'];
        $repositories = new Repositories($this->config, $this->logger);
        $repositoryData = $repositories->getRepositoryData($repositoryId);
        if (empty($repositoryData)) {
            $this->responder->setError('Repository not found in database.');
            return false;
        }
        $this->responder->setPayload($repositoryData);
        return true;
    }
}
