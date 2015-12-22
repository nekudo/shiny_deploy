<?php namespace ShinyDeploy\Action\WsWorkerAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\WsChangedFilesResponder;
use ShinyDeploy\Responder\WsLogResponder;
use ShinyDeploy\Responder\WsNotificationResponder;

class GetChangedFiles extends WsWorkerAction
{
    /**
     * Fetch a list of changed files between two revisions.
     *
     * @param int $id
     * @return boolean
     * @throws MissingDataException
     */
    public function __invoke($id)
    {
        $deploymentId = (int)$id;
        if (empty($deploymentId)) {
            throw new MissingDataException('DeploymentId can not be empty');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            throw new RuntimeException('Could not get encryption key.');
        }

        // init stuff:
        $logResponder = new WsLogResponder($this->config, $this->logger);
        $logResponder->setClientId($this->clientId);
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deployment = $deployments->getDeployment($deploymentId);
        $deployment->setLogResponder($logResponder);

        // get changed files and respond:
        $logResponder->log('Collecting changed files...');
        $result = $deployment->deploy(true);
        if ($result === false) {
            $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
            $notificationResponder->setClientId($this->clientId);
            $notificationResponder->send('Error fetching changed files. Check log for details.', 'danger');
            return false;
        }
        $changedFiles = $deployment->getChangedFiles();
        $changedFilesCount = count($changedFiles);
        $logResponder->log($changedFilesCount . ' changed files found.');

        // respond to client:
        $changedFilesResponder = new WsChangedFilesResponder($this->config, $this->logger);
        $changedFilesResponder->setClientId($this->clientId);
        $changedFilesResponder->respond($changedFiles);
        return true;
    }
}
