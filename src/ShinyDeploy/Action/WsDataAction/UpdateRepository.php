<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;
use Valitron\Validator;

class UpdateRepository extends WsDataAction
{
    public function __invoke($actionPayload)
    {
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

        // check if url is okay:
        $urlCheckResult = $repositoriesDomain->checkUrl($repositoryData);
        if ($urlCheckResult === false) {
            $this->responder->setError('Repository check failed. Please check URL, username and password.');
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
}
