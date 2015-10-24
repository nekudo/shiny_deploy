<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Responder\WsChangedFilesResponder;
use ShinyDeploy\Responder\WsLogResponder;
use ShinyDeploy\Responder\WsSetRemoteRevisionResponder;

class Deploy extends Action
{
    public function __invoke($deploymentId, $clientId, $listOnly = false)
    {
        $logResponder = new WsLogResponder($this->config, $this->logger);
        $logResponder->setClientId($clientId);

        try {
            // check required arguments:
            $deploymentId = (int)$deploymentId;
            if (empty($deploymentId)) {
                throw new RuntimeException('Deployment-ID can not be empty');
            }

            // init deployment:
            $deployments = new Deployments($this->config, $this->logger);
            $deployment = $deployments->getDeployment($deploymentId);
            $deployment->setLogResponder($logResponder);

            // start deployment:
            $logResponder->log('Starting deployment...', 'default', 'DeployService');
            $result = $deployment->deploy($listOnly);

            // return changed files:
            if ($listOnly === true && $result === true) {
                $changedFiles = $deployment->getChangedFiles();
                $changedFilesCount = count($changedFiles);
                $logResponder->log(
                    $changedFilesCount . ' changed files found. Sending list...',
                    'default',
                    'DeployService'
                );
                $changedFilesResponder = new WsChangedFilesResponder($this->config, $this->logger);
                $changedFilesResponder->setClientId($clientId);
                $changedFilesResponder->respond($changedFiles);
            }

            // update revision in browser:
            if ($result === true) {
                $revision = $deployment->getRemoteRevision();
                $responder = new WsSetRemoteRevisionResponder($this->config, $this->logger);
                $responder->setClientId($clientId);
                $responder->respond($revision);
            }

            $logResponder->log("Shiny, everything done.", 'success', 'DeployService');

        } catch (RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $logResponder->log('Exception: ' . $e->getMessage() . ' Aborting.', 'error', 'DeployService');
            return false;
        }
    }
}
