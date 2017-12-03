<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Exceptions\InvalidPayloadException;
use Valitron\Validator;

class UpdateServer extends WsDataAction
{
    /**
     * Updates server data in database.
     *
     * @param array $actionPayload
     * @throws InvalidPayloadException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     * @return bool
     */
    public function __invoke(array $actionPayload) : bool
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['serverData'])) {
            throw new InvalidPayloadException('Invalid updateServer request received.');
        }
        $serverData = $actionPayload['serverData'];
        $servers = new Servers($this->config, $this->logger);

        // validate input:
        $validator = new Validator($serverData);
        $validator->rules($servers->getUpdateRules());
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

        // update server:
        $servers->setEnryptionKey($encryptionKey);
        $addResult = $servers->updateServer($serverData);
        if ($addResult === false) {
            $this->responder->setError('Could not update server.');
            return false;
        }
        return true;
    }
}
