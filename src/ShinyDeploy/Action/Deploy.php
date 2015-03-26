<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Core\Server\Server;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Domain\Deploy as DeployDomain;
use ShinyDeploy\Responder\WsGatewayResponder;

class Deploy extends Action
{
    /** @var  WsGatewayResponder $responder */
    protected $responder;

    /** @var  DeployDomain $deployDomain */
    protected $deployDomain;

    /** @var  Git $gitDomain */
    protected $gitDomain;

    /** @var  Repository $repositoryDomain */
    protected $repositoryDomain;

    /** @var  Server $server */
    protected $server;

    public function __invoke($clientId, $idSource, $idTarget)
    {
        try {
            $this->deployDomain = new DeployDomain($this->config, $this->logger);
            $this->gitDomain = new Git($this->config, $this->logger);
            $this->repositoryDomain = new Repository($this->config, $this->logger);
            $responder = new WsGatewayResponder($this->config, $this->logger);
            $responder->setClientId($clientId);
            $this->responder = $responder;

            // check if git executable is available:
            if ($this->checkGitExecutable() === false) {
                return false;
            }

            // prepare local repository:
            if ($this->prepareRepository($idSource) === false) {
                return false;
            }

            // check remove server connectivity:
            if ($this->checkRemoteServer($idTarget) === false) {
                return false;
            }

            // deploy changes:
            if ($this->deploy($idSource, $idTarget) === false) {
                return false;
            }

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $this->responder->log('Exception: ' . $e->getMessage() . ' Aborting.', 'error', 'DeployAction');
            return false;
        }
    }

    /**
     * Check weather git is executable.
     * @return bool
     */
    protected function checkGitExecutable()
    {
        $versionString = $this->gitDomain->getVersion();
        if ($versionString === false) {
            $this->responder->log('Git executable not found. Aborting job.', 'error', 'DeployAction');
            return false;
        }
        $this->responder->log($versionString . ' detected.', 'default', 'DeployAction');
        return true;
    }

    /**
     * If local repository does not exist it will be pulled from git. It it exists it will be updated.
     *
     * @param string $idSource
     * @return bool
     */
    protected function prepareRepository($idSource)
    {
        $repoPath = $this->repositoryDomain->createLocalPath($idSource);
        if ($this->repositoryDomain->exists($idSource) === false) {
            $this->responder->log('Local repository not found. Starting git clone...', 'default', 'DeployAction');
            $response = $this->gitDomain->gitClone($idSource, $repoPath);
            $this->responder->log($response, 'default', 'Git');
            if (strpos($response, 'done.') !== false) {
                $this->responder->log('Repository successfully cloned.', 'success', 'DeployAction');
                return true;
            }
        } else {
            $this->responder->log('Local repository found. Starting update...', 'default', 'DeployAction');
            $response = $this->gitDomain->gitPull($idSource, $repoPath);
            if (strpos($response, 'up-to-date') !== false ||
                (stripos($response, 'updating') !== false && strpos($response, 'done.') !== false)) {
                $this->responder->log('Local repository successfully updated.', 'success', 'DeployAction');
                return true;
            }
        }
        $this->responder->log('Error updating repository. Aborting.', 'error', 'DeployAction');
        return false;
    }

    /**
     * Checks if connection to remote server is possible.
     *
     * @param string $idTarget
     * @return bool
     */
    protected function checkRemoteServer($idTarget)
    {
        $this->responder->log('Checking remote server...', 'default', 'DeployAction');
        $serverConfig = $this->config->get('targets.'.$idTarget);
        $this->server = $this->deployDomain->getServer($serverConfig['type']);
        $connectivity = $this->deployDomain->checkConnectivity($this->server, $serverConfig['credentials']);
        if ($connectivity === true) {
            $this->responder->log('Successfully connected to remote server.', 'success', 'DeployAction');
            return true;
        }
        $this->responder->log('Connection to remote server failed. Aborting.', 'error', 'DeployAction');
        return false;
    }

    /**
     * Deploys changes from local repository to remote server.
     *
     * @param string $idSource
     * @param string $idTarget
     * @return bool
     */
    protected function deploy($idSource, $idTarget)
    {
        if (empty($this->server)) {
            throw new \RuntimeException('No instance of target server.');
        }
        $targetConfig = $this->config->get('targets.'.$idTarget);

        // get revision on target server:
        $remoteRevision = $this->deployDomain->getRemoteRevision($this->server, $targetConfig['path'].'/REVISION');
        if (empty($remoteRevision)) {
            $this->responder->log('Could not estimate revision on remote server.', 'error', 'DeployAction');
            return false;
        }
        $this->responder->log('Remote server is at revision: ' . $remoteRevision, 'default', 'DeployAction');

        // get revision of local repository:
        $repoPath = $this->repositoryDomain->createLocalPath($idSource);
        $localRevision = $this->deployDomain->getLocalRevision($repoPath, $this->gitDomain);
        if (empty($localRevision)) {
            $this->responder->log('Could not estimate revision of local repository.', 'error', 'DeployAction');
            return false;
        }
        $this->responder->log('Local repository is at revision: ' . $localRevision, 'default', 'DeployAction');

        // stop processing if remote server is up to date:
        if ($localRevision === $remoteRevision) {
            $this->responder->log('Remote server is up to date.', 'info', 'DeployAction');
            return true;
        }

        // collect file changes:
        $this->responder->log('Collecting file changes...', 'default', 'DeployAction');
        $changedFiles = $this->deployDomain->getChangedFiles(
            $repoPath,
            $localRevision,
            $remoteRevision,
            $this->gitDomain
        );
        if (empty($changedFiles)) {
            $this->responder->log('Could not estimate changed files.', 'error', 'DeployAction');
            return false;
        }
        $uploadCount = count($changedFiles['upload']);
        $deleteCount = count($changedFiles['upload']);
        if ($uploadCount === 0 && $deleteCount === 0) {
            $this->responder->log('Noting to upload or delete.', 'info', 'DeployAction');
            return true;
        }
        $this->responder->log(
            'Diff complete: Files to upload: '.$uploadCount.' Files to delete: ' . $deleteCount,
            'default',
            'DeployAction'
        );

        // Start upload/delete process:
    }
}
