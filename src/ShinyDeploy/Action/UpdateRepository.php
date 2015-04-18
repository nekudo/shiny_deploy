<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;
use Valitron\Validator;

class UpdateRepository extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        $this->setResponse($responder);
        if (!isset($actionPayload['repositoryData'])) {
            throw new WebsocketException('Invalid updateRepository request received.');
        }
        $repositoryData = $actionPayload['repositoryData'];
        $repositoriesDomain = new Repositories($this->config, $this->logger);

        // validate input:
        $validator = new Validator($repositoryData);
        $validator->rules($repositoriesDomain->getUpdateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // update server:
        $addResult = $repositoriesDomain->updateRepository($repositoryData);
        if ($addResult === false) {
            $this->responder->setError('Could not update repository.');
            return false;
        }
        return true;
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
