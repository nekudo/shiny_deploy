<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class GetRepositoryData extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['repositoryId'])) {
            throw new WebsocketException('Invalid getRepositoryData request received.');
        }
        $repositoryId = (int)$actionPayload['repositoryId'];
        $repositoriesDomain = new Repositories($this->config, $this->logger);
        $repositoryData = $repositoriesDomain->getRepositoryData($repositoryId);
        if (empty($repositoryData)) {
            $this->responder->setError('Repository not found in database.');
            return false;
        }
        $this->responder->setPayload($repositoryData);
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
