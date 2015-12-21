<?php namespace ShinyDeploy\Action\WsWorkerAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\WsLogResponder;
use ShinyDeploy\Responder\WsNotificationResponder;
use ShinyDeploy\Responder\WsSetRemoteRevisionResponder;

class Deploy extends WsWorkerAction
{
    /**
     * Deploy file changes from repository to target server.
     *
     * @param int $id
     * @return boolean
     * @throws MissingDataException
     */
    public function __invoke($id)
    {
        $deploymentId = (int)$id;
        if (empty($deploymentId)) {
            throw new MissingDataException('Deployment-ID can not be empty');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            throw new RuntimeException('Could not get encryption key.');
        }

        // Init stuff
        $logResponder = new WsLogResponder($this->config, $this->logger);
        $logResponder->setClientId($this->clientId);
        $remoteRevisionResponder = new WsSetRemoteRevisionResponder($this->config, $this->logger);
        $remoteRevisionResponder->setClientId($this->clientId);
        $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
        $notificationResponder->setClientId($this->clientId);
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deployment = $deployments->getDeployment($deploymentId);
        $deployment->setLogResponder($logResponder);

        // Start deployment
        $logResponder->log('Starting deployment...');
        $result = $deployment->deploy(false);
        if ($result === false) {
            $notificationResponder->send('Deployment failed. Check log for details.', 'danger');
            return false;
        }

        // Send updated revision to client:
        $revision = $deployment->getRemoteRevision();
        $remoteRevisionResponder->respond($revision);
        $logResponder->success('Deployment successfully completed.');

        // Send success notfication
        $notificationResponder->send('Deployment successfully completed.', 'success');
        return true;
    }
}
