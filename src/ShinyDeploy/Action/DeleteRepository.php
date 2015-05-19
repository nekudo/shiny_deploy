<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Domain\Repository;
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
        $repositoryDomain = new Repository($this->config, $this->logger);
        $repositoryData = $repositoriesDomain->getRepositoryData($repositoryId);
        $repositoryPath = $repositoryDomain->getLocalPath($repositoryData['url']);

        // remove repository from database:
        $deleteResult = $repositoriesDomain->deleteRepository($repositoryId);
        if ($deleteResult === false) {
            $this->responder->setError('Could not remove repository from database.');
            return false;
        }

        // trigger repository file removal:
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $actionPayload['clientId'] = $this->clientId;
        $actionPayload['repoPath'] = $repositoryPath;
        $payload = json_encode($actionPayload);
        $client->doBackground('deleteRepository', $payload);
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
