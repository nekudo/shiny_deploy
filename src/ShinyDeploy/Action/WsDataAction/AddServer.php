<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\WebsocketException;
use Valitron\Validator;

class AddServer extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['serverData'])) {
            throw new WebsocketException('Invalid addServer request received.');
        }
        $serverData = $actionPayload['serverData'];
        $serversDomain = new Servers($this->config, $this->logger);

        // validate input:
        $validator = new Validator($serverData);
        $validator->rules($serversDomain->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // add server:
        $addResult = $serversDomain->addServer($serverData);
        if ($addResult === false) {
            $this->responder->setError('Could not add server to database.');
            return false;
        }
        return true;
    }
}
