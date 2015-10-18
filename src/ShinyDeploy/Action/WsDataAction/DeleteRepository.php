<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;

class DeleteRepository extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['repositoryId'])) {
            throw new WebsocketException('Invalid deleteRepository request received.');
        }
        $repositoryId = (int)$actionPayload['repositoryId'];
        $repositories = new Repositories($this->config, $this->logger);
        $repository = $repositories->getRepository($repositoryId);
        $repositoryPath = $repository->getLocalPath();

        // check if repository still in use:
        if ($repositories->repositoryInUse($repositoryId) === true) {
            $this->responder->setError('This repository is still used in a deployment.');
            return false;
        }

        // remove repository from database:
        $deleteResult = $repositories->deleteRepository($repositoryId);
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
}
