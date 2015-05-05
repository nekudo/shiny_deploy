<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Server\Server;
use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Domain\Deploy as DeployDomain;
use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Responder\WsLogResponder;

class Deploy extends WsTriggerAction
{
    /** @var  WsLogResponder $logResponder */
    protected $logResponder;

    /** @var  DeployDomain $deployDomain */
    protected $deployDomain;

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

    public function __invoke(array $actionPayload)
    {
        try {
            $this->deployDomain = new DeployDomain($this->config, $this->logger);
            $this->deploymentsDomain = new Deployments($this->config, $this->logger);
            $this->repositoryDomain = new Repository($this->config, $this->logger);
            $this->repositoriesDomain = new Repositories($this->config, $this->logger);
            $this->serversDomain = new Servers($this->config, $this->logger);
            $this->gitDomain = new Git($this->config, $this->logger);
            $this->logResponder = new WsLogResponder($this->config, $this->logger);
            $this->logResponder->setClientId($this->clientId);

            // check required arguments:
            if (empty($actionPayload['deploymentId'])) {
                throw new \RuntimeException('Deployment-ID can not be empty');
            }

            // collect required data:
            $deploymentData = $this->deploymentsDomain->getDeploymentData($actionPayload['deploymentId']);
            if (empty($deploymentData)) {
                throw new \RuntimeException('Could not load deployment data.');
            }
            $repositoryData = $this->repositoriesDomain->getRepositoryData($deploymentData['repository_id']);
            if (empty($repositoryData)) {
                throw new \RuntimeException('Could not load repository data.');
            }
            $serverData = $this->serversDomain->getServerData($deploymentData['server_id']);
            if (empty($serverData)) {
                throw new \RuntimeException('Could not load server data.');
            }

            // check if git executable is available:
            if ($this->checkGitExecutable() === false) {
                return false;
            }

            // prepare local repository:
            if ($this->prepareRepository($repositoryData) === false) {
                return false;
            }

            // check remove server connectivity:
            if ($this->checkRemoteServer($serverData) === false) {
                return false;
            }

            // deploy changes:
            if ($this->deploy($idSource, $idTarget) === false) {
                return false;
            }

            // @todo update remote revision file

            $this->logResponder->log("\nShiny, everything done. Your project is up to date.", 'success', 'DeployAction');

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $this->logResponder->log('Exception: ' . $e->getMessage() . ' Aborting.', 'error', 'DeployAction');
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
            $this->logResponder->log('Git executable not found. Aborting job.', 'error', 'DeployAction');
            return false;
        }
        $this->logResponder->log($versionString . ' detected.', 'default', 'DeployAction');
        return true;
    }

    /**
     * If local repository does not exist it will be pulled from git. It it exists it will be updated.
     *
     * @param array $repositoryData
     * @return bool
     */
    protected function prepareRepository(array $repositoryData)
    {
        $repoPath = $this->repositoryDomain->createLocalPath($repositoryData['url']);
        if ($this->repositoryDomain->exists($repoPath) === false) {
            $this->logResponder->log('Local repository not found. Starting git clone...', 'default', 'DeployAction');
            $response = $this->gitDomain->gitClone($repositoryData, $repoPath);
            if (strpos($response, 'done.') !== false) {
                $this->logResponder->log('Repository successfully cloned.', 'success', 'DeployAction');
                return true;
            }
        } else {
            $this->logResponder->log('Local repository found. Starting update...', 'default', 'DeployAction');
            $response = $this->gitDomain->gitPull($repositoryData, $repoPath);
            if (strpos($response, 'up-to-date') !== false ||
                (stripos($response, 'updating') !== false && strpos($response, 'done.') !== false)) {
                $this->logResponder->log('Local repository successfully updated.', 'success', 'DeployAction');
                return true;
            }
        }
        $this->logResponder->log('Error updating repository. Aborting.', 'error', 'DeployAction');
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
        $this->logResponder->log('Checking remote server...', 'default', 'DeployAction');
        $serverConfig = $this->config->get('targets.'.$idTarget);
        $this->server = $this->deployDomain->getServer($serverConfig['type']);
        $connectivity = $this->deployDomain->checkConnectivity($this->server, $serverConfig['credentials']);
        if ($connectivity === true) {
            $this->logResponder->log('Successfully connected to remote server.', 'success', 'DeployAction');
            return true;
        }
        $this->logResponder->log('Connection to remote server failed. Aborting.', 'error', 'DeployAction');
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
        if ($remoteRevision === false) {
            $this->logResponder->log('Could not estimate revision on remote server.', 'error', 'DeployAction');
            return false;
        }
        if ($remoteRevision === '-1') {
            $this->logResponder->log('Remote revision not found - deploying all files.', 'info', 'DeployAction');
        } else {
            $this->logResponder->log('Remote server is at revision: ' . $remoteRevision, 'default', 'DeployAction');
        }

        // get revision of local repository:
        $repoPath = $this->repositoryDomain->createLocalPath($idSource);
        $localRevision = $this->deployDomain->getLocalRevision($repoPath, $this->gitDomain);
        if (empty($localRevision)) {
            $this->logResponder->log('Could not estimate revision of local repository.', 'error', 'DeployAction');
            return false;
        }
        $this->logResponder->log('Local repository is at revision: ' . $localRevision, 'default', 'DeployAction');

        // stop processing if remote server is up to date:
        if ($localRevision === $remoteRevision) {
            $this->logResponder->log('Remote server is up to date.', 'info', 'DeployAction');
            return true;
        }

        // collect file changes:
        $this->logResponder->log('Collecting file changes...', 'default', 'DeployAction');
        $changedFiles = $this->deployDomain->getChangedFiles(
            $repoPath,
            $localRevision,
            $remoteRevision,
            $this->gitDomain
        );
        if (empty($changedFiles)) {
            $this->logResponder->log('Could not estimate changed files.', 'error', 'DeployAction');
            return false;
        }
        $uploadCount = count($changedFiles['upload']);
        $deleteCount = count($changedFiles['delete']);
        if ($uploadCount === 0 && $deleteCount === 0) {
            $this->logResponder->log('Noting to upload or delete.', 'info', 'DeployAction');
            return true;
        }
        $this->logResponder->log(
            'Diff complete. (Files to upload: '.$uploadCount.' - Files to delete: ' . $deleteCount . ')',
            'default',
            'DeployAction'
        );

        // Start upload/delete process:
        $repoPath = rtrim($repoPath, '/') . '/';
        $remotePath = rtrim($targetConfig['path'], '/') . '/';
        if ($uploadCount > 0) {
            $this->logResponder->log('Starting file uploads...', 'default', 'DeployAction');
            foreach ($changedFiles['upload'] as $file) {
                $uploadStart = microtime(true);
                $result = $this->server->upload($repoPath.$file, $remotePath.$file);
                $uploadEnd = microtime(true);
                $uploadDuration = round($uploadEnd - $uploadStart, 2);
                if ($result === true) {
                    $this->logResponder->log(
                        'Uploading ' . $file . ': success ('.$uploadDuration.'s)',
                        'info',
                        'DeployAction'
                    );
                } else {
                    $this->logResponder->log('Uploading ' . $file . ': failed', 'danger', 'DeployAction');
                }
            }
        }
        if ($deleteCount > 0) {
            $this->logResponder->log('Removing files...', 'default', 'DeployAction');
            foreach ($changedFiles['delete'] as $file) {
                $result = $this->server->delete($remotePath.$file);
                if ($result === true) {
                    $this->logResponder->log('Deleting ' . $file . ': success', 'info', 'DeployAction');
                } else {
                    $this->logResponder->log('Deleting ' . $file . ': failed', 'danger', 'DeployAction');
                }
            }
        }

        // Update remote revision file:
        if ($this->server->putContent($localRevision, $remotePath.'REVISION') === false) {
            $this->logResponder->log('Could not update remote revision file.', 'error', 'DeployAction');
            return false;
        } else {
            $this->logResponder->log('Revision file successfully updated.', 'default', 'DeployAction');
        }

        return true;
    }
}
