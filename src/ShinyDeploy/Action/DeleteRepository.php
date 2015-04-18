<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class DeleteRepository extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['repositoryId'])) {
            throw new WebsocketException('Invalid deleteRepository request received.');
        }
        $repositoryId = (int)$actionPayload['repositoryId'];
        $repositoriesDomain = new Repositories($this->config, $this->logger);

        // remove server:
        $addResult = $repositoriesDomain->deleteRepository($repositoryId);
        if ($addResult === false) {
            $this->responder->setError('Could not remove repository from database.');
            return false;
        }
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
