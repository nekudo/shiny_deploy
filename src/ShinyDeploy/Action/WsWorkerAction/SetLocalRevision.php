<?php namespace ShinyDeploy\Action\WsWorkerAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\NullResponder;
use ShinyDeploy\Responder\WsSetLocalRevisionResponder;

class SetLocalRevision extends WsWorkerAction
{
    /**
     * Fetches and returns revision of a local repository.
     *
     * @param array $params
     * @return bool
     */
    public function __invoke(array $params) : bool
    {
        if (!isset($params['deploymentId'])) {
            throw new MissingDataException('Required parameter missing.');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            throw new RuntimeException('Could not get encryption key.');
        }

        $deploymentId = (int)$params['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deployment = $deployments->getDeployment($deploymentId);
        $logResponder = new NullResponder($this->config, $this->logger);
        $deployment->setLogResponder($logResponder);
        $revision = $deployment->getLocalRevision();
        $responder = new WsSetLocalRevisionResponder($this->config, $this->logger);
        $responder->setClientId($this->clientId);
        $responder->respond($revision);
        return true;
    }
}
