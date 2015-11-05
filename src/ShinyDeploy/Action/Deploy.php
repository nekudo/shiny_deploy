<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Responder\WsLogResponder;
use ShinyDeploy\Responder\WsNotificationResponder;
use ShinyDeploy\Responder\WsSetRemoteRevisionResponder;

class Deploy extends Action
{
    public function __invoke($id, $clientId)
    {
        try {
            $deploymentId = (int)$id;
            if (empty($deploymentId)) {
                throw new RuntimeException('Deployment-ID can not be empty');
            }

            // Init stuff
            $logResponder = new WsLogResponder($this->config, $this->logger);
            $logResponder->setClientId($clientId);
            $remoteRevisionResponder = new WsSetRemoteRevisionResponder($this->config, $this->logger);
            $remoteRevisionResponder->setClientId($clientId);
            $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
            $notificationResponder->setClientId($clientId);
            $deployments = new Deployments($this->config, $this->logger);
            $deployment = $deployments->getDeployment($deploymentId);
            $deployment->setLogResponder($logResponder);

            // Start deployment
            $logResponder->log('Starting deployment...', 'default', 'DeployService');
            $result = $deployment->deploy(false);
            if ($result === false) {
                $notificationResponder->send('Deployment failed. Check log for details.', 'danger');
                return false;
            }

            // Send updated revision to client:
            $revision = $deployment->getRemoteRevision();
            $remoteRevisionResponder->respond($revision);
            $logResponder->log("Deployment successfully completed.", 'success', 'DeployService');

            // Send success notfication
            $notificationResponder->send('Deployment successfully completed.', 'success');

        } catch (RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }
}
