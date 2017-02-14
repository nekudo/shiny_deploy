<?php namespace ShinyDeploy\Action\WsWorkerAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\DeploymentLogs;
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
     * @param array $tasksToRun
     * @return boolean
     * @throws MissingDataException
     */
    public function __invoke($id, array $tasksToRun = [])
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

        // Init responder
        $logResponder = new WsLogResponder($this->config, $this->logger);
        $logResponder->setClientId($this->clientId);
        $remoteRevisionResponder = new WsSetRemoteRevisionResponder($this->config, $this->logger);
        $remoteRevisionResponder->setClientId($this->clientId);
        $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
        $notificationResponder->setClientId($this->clientId);

        // Log deployment start
        $deploymentLogs = new DeploymentLogs($this->config, $this->logger);
        $logId = $deploymentLogs->logDeploymentStart($deploymentId, 'GUI');

        // Start deployment
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deployment = $deployments->getDeployment($deploymentId);
        $deployment->setLogResponder($logResponder);
        $deployment->setTasksToRun($tasksToRun);
        $logResponder->log('Starting deployment...');
        $result = $deployment->deploy(false);
        if ($result === false) {
            $deploymentLogs->logDeploymentError($logId);
            $notificationResponder->send('Deployment failed. Check log for details.', 'danger');
            return false;
        }

        // Log deployment success:
        $deploymentLogs->logDeploymentSuccess($logId);

        // Send updated revision to client:
        $revision = $deployment->getRemoteRevision();
        $remoteRevisionResponder->respond($revision);
        $logResponder->success('Deployment successfully completed.');

        // Send success notfication
        $notificationResponder->send('Deployment successfully completed.', 'success');
        return true;
    }
}
