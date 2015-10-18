<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Server\Server;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Responder\WsChangedFilesResponder;
use ShinyDeploy\Responder\WsLogResponder;

class Deploy extends Action
{
    /** @var  WsLogResponder $logResponder */
    protected $logResponder;

    /** @var  WsChangedFilesResponder */
    protected $changedFilesResponder;

    /** @var  DeployDomain $deployDomain */
    protected $deploymentDomain;

    /** @var  Git $gitDomain */
    protected $gitDomain;

    /** @var  Repository $repositoryDomain */
    protected $repositoryDomain;

    /** @var  Repositories $repositoriesDomain */
    protected $repositoriesDomain;

    /** @var  Deployments $deploymentsDomain */
    protected $deploymentsDomain;

    /** @var  Servers $serversDomain */
    protected $serversDomain;

    /** @var  Server $server */
    protected $server;

    protected $listOnly = false;

    public function __invoke($deploymentId, $clientId, $listOnly = false)
    {
        try {
            // check required arguments:
            $deploymentId = (int)$deploymentId;
            if (empty($deploymentId)) {
                throw new \RuntimeException('Deployment-ID can not be empty');
            }
            if (empty($clientId)) {
                throw new \RuntimeException('clientId can not be empty.');
            }

            // init deployment:
            $deployments = new Deployments($this->config, $this->logger);
            $deployment = $deployments->getDeployment($deploymentId);
            $logResponder = new WsLogResponder($this->config, $this->logger);
            $logResponder->setClientId($clientId);
            $deployment->setLogResponder($logResponder);

            // start deployment:
            $logResponder->log('Starting deployment...', 'default', 'DeployService');
            $result = $deployment->deploy($listOnly);

            // return changed files:
            if ($listOnly === true && $result === true) {
                $changedFiles = $deployment->getChangedFiles();
                $this->changedFilesResponder = new WsChangedFilesResponder($this->config, $this->logger);
                $this->changedFilesResponder->setClientId($clientId);
                $this->changedFilesResponder->respond($changedFiles);
            }

            $logResponder->log("Shiny, everything done.", 'success', 'DeployService');

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $logResponder->log('Exception: ' . $e->getMessage() . ' Aborting.', 'error', 'DeployService');
            return false;
        }
    }
}
