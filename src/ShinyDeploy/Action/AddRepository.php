<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;
use Valitron\Validator;

class AddRepository extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['repositoryData'])) {
            throw new WebsocketException('Invalid addRepository request received.');
        }
        $repositoryData = $actionPayload['repositoryData'];
        $repositoriesDomain = new Repositories($this->config, $this->logger);

        // validate input:
        $validator = new Validator($repositoryData);
        $validator->rules($repositoriesDomain->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // check if url is okay:
        $urlCheckResult = $repositoriesDomain->checkUrl($repositoryData);
        if ($urlCheckResult === false) {
            $this->responder->setError('Repository check failed. Please check URL, username and password.');
            return false;
        }

        // add repository:
        $repositoryId = $repositoriesDomain->addRepository($repositoryData);
        if ($repositoryId === false) {
            $this->responder->setError('Could not add repository to database.');
            return false;
        }

        // trigger initial cloning:
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $actionPayload['clientId'] = $this->clientId;
        $actionPayload['repositoryId'] = $repositoryId;
        $payload = json_encode($actionPayload);
        $client->doBackground('cloneRepository', $payload);
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
