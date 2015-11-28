<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\WebsocketException;
use Valitron\Validator;

class AddServer extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['serverData'])) {
            throw new WebsocketException('Invalid addServer request received.');
        }
        $serverData = $actionPayload['serverData'];
        $servers = new Servers($this->config, $this->logger);

        // validate input:
        $validator = new Validator($serverData);
        $validator->rules($servers->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        // add server:
        $servers->setEnryptionKey($encryptionKey);
        $addResult = $servers->addServer($serverData);
        if ($addResult === false) {
            $this->responder->setError('Could not add server to database.');
            return false;
        }
        return true;
    }
}
