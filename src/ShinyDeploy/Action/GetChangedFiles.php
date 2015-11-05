<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Responder\WsChangedFilesResponder;
use ShinyDeploy\Responder\WsLogResponder;
use ShinyDeploy\Responder\WsNotificationResponder;

class GetChangedFiles extends Action
{
    public function __invoke($id, $clientId)
    {
        try {
            $deploymentId = (int)$id;
            if (empty($deploymentId)) {
                throw new RuntimeException('Deployment-ID can not be empty');
            }

            // init stuff:
            $logResponder = new WsLogResponder($this->config, $this->logger);
            $logResponder->setClientId($clientId);
            $deployments = new Deployments($this->config, $this->logger);
            $deployment = $deployments->getDeployment($deploymentId);
            $deployment->setLogResponder($logResponder);

            // get changed files and respond:
            $logResponder->log('Collecting changed files...', 'default', 'DeployService');
            $result = $deployment->deploy(true);
            if ($result === false) {
                $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
                $notificationResponder->setClientId($clientId);
                $notificationResponder->send('Error fetching changed files. Check log for details.', 'danger');
                return false;
            }
            $changedFiles = $deployment->getChangedFiles();
            $changedFilesCount = count($changedFiles);
            $logResponder->log(
                $changedFilesCount . ' changed files found. Sending list...',
                'default',
                'DeployService'
            );

            // respond to client:
            $changedFilesResponder = new WsChangedFilesResponder($this->config, $this->logger);
            $changedFilesResponder->setClientId($clientId);
            $changedFilesResponder->respond($changedFiles);
            return true;

        } catch (RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }
}
