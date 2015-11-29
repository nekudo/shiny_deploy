<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Responder\NullResponder;
use ShinyDeploy\Responder\WsSetRemoteRevisionResponder;

class SetRemoteRevision extends Action
{
    /**
     * Fetches remote repository revision
     *
     * @param array $params
     * @return bool
     */
    public function __invoke($params)
    {
        if (!isset($params['deploymentId'])) {
            throw new RuntimeException('Required parameter missing.');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        $deploymentId = (int)$params['deploymentId'];
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deployment = $deployments->getDeployment($deploymentId);
        $logResponder = new NullResponder($this->config, $this->logger);
        $deployment->setLogResponder($logResponder);
        $revision = $deployment->getRemoteRevision();
        $responder = new WsSetRemoteRevisionResponder($this->config, $this->logger);
        $responder->setClientId($params['clientId']);
        $responder->respond($revision);
        return true;
    }
}
