<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Core\Server\Server;
use ShinyDeploy\Domain\Deployments;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Domain\Deploy as DeployDomain;
use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Responder\WsChangedFilesResponder;
use ShinyDeploy\Responder\WsLogResponder;

class Deploy extends Action
{
    /** @var  WsLogResponder $logResponder */
    protected $logResponder;

    /** @var  WsChangedFilesResponder */
    protected $changedFilesResponder;

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

    protected $listOnly = false;

    public function __invoke($deploymentId, $clientId, $listOnly = false)
    {
        try {
            $this->listOnly = $listOnly;
            $this->deployDomain = new DeployDomain($this->config, $this->logger);
            $this->deploymentsDomain = new Deployments($this->config, $this->logger);
            $this->repositoryDomain = new Repository($this->config, $this->logger);
            $this->repositoriesDomain = new Repositories($this->config, $this->logger);
            $this->serversDomain = new Servers($this->config, $this->logger);
            $this->gitDomain = new Git($this->config, $this->logger);
            $this->logResponder = new WsLogResponder($this->config, $this->logger);
            $this->logResponder->setClientId($clientId);
            if ($listOnly === true) {
                $this->changedFilesResponder = new WsChangedFilesResponder($this->config, $this->logger);
                $this->changedFilesResponder->setClientId($clientId);
            }

            // check required arguments:
            $deploymentId = (int)$deploymentId;
            if (empty($deploymentId)) {
                throw new \RuntimeException('Deployment-ID can not be empty');
            }

            // collect required data:
            $deploymentData = $this->deploymentsDomain->getDeploymentData($deploymentId);
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

            // check if repository is reachable:
            if ($this->repositoriesDomain->checkUrl($repositoryData) === false) {
                throw new \RuntimeException('Could not reach repository. Check url and credentials.');
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
            if ($this->deploy($repositoryData, $serverData, $deploymentData) === false) {
                return false;
            }
            if ($this->listOnly === false) {
                $this->logResponder->log(
                    "\nShiny, everything done. Your project is up to date.",
                    'success',
                    'DeployWorker');
            }
        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $this->logResponder->log('Exception: ' . $e->getMessage() . ' Aborting.', 'error', 'DeployWorker');
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
            $this->logResponder->log('Git executable not found. Aborting job.', 'error', 'DeployWorker');
            return false;
        }
        $this->logResponder->log($versionString . ' detected.', 'default', 'DeployWorker');
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
        $repoUrl = $this->repositoriesDomain->getCredentialsUrl($repositoryData);
        if ($this->repositoryDomain->exists($repoPath) === false) {
            $this->logResponder->log('Local repository not found. Starting git clone...', 'default', 'DeployWorker');
            $response = $this->gitDomain->gitClone($repoUrl, $repoPath);
            if (strpos($response, 'done.') !== false) {
                $this->logResponder->log('Repository successfully cloned.', 'success', 'DeployWorker');
                return true;
            }
        } else {
            $this->logResponder->log('Local repository found. Starting update...', 'default', 'DeployWorker');
            $response = $this->gitDomain->gitPull($repoUrl, $repoPath);
            if (strpos($response, 'up-to-date') !== false ||
                (stripos($response, 'updating') !== false && strpos($response, 'done.') !== false)) {
                $this->logResponder->log('Local repository successfully updated.', 'success', 'DeployWorker');
                return true;
            }
        }
        $this->logResponder->log('Error updating repository. Aborting.', 'error', 'DeployWorker');
        return false;
    }

    /**
     * Checks if connection to remote server is possible.
     *
     * @param array $serverData
     * @return bool
     */
    protected function checkRemoteServer(array $serverData)
    {
        $this->logResponder->log('Checking remote server...', 'default', 'DeployWorker');
        $this->server = $this->deployDomain->getServer($serverData['type']);
        $connectivity = $this->deployDomain->checkConnectivity($this->server, $serverData);
        if ($connectivity === true) {
            $this->logResponder->log('Successfully connected to remote server.', 'success', 'DeployWorker');
            return true;
        }
        $this->logResponder->log('Connection to remote server failed. Aborting.', 'error', 'DeployWorker');
        return false;
    }

    /**
     * Deploys changes from local repository to remote server.
     *
     * @param array $repoData
     * @param array $serverData
     * @param array $deploymentData
     * @return bool
     */
    protected function deploy(array $repoData, array $serverData, array $deploymentData)
    {
        if (empty($this->server)) {
            throw new \RuntimeException('No instance of target server.');
        }

        // get revision on target server:
        $remotePath = rtrim($serverData['root_path']);
        $remotePath .= '/' . trim($deploymentData['target_path']) . '/';
        $revisionFilePath = $remotePath . 'REVISION';
        $remoteRevision = $this->deployDomain->getRemoteRevision($this->server, $revisionFilePath);
        if ($remoteRevision === false) {
            $this->logResponder->log(
                'Could not estimate revision on remote server. Check if path is correct!',
                'error',
                'DeployWorker'
            );
            return false;
        }
        if ($remoteRevision === '-1') {
            $this->logResponder->log('Remote revision not found - deploying all files.', 'info', 'DeployWorker');
        } else {
            $this->logResponder->log('Remote server is at revision: ' . $remoteRevision, 'default', 'DeployWorker');
        }

        $repoPath = $this->repositoryDomain->createLocalPath($repoData['url']);

        // switch to selected branch:
        $switchResult = $this->gitDomain->switchBranch($repoPath, $deploymentData['branch']);
        if ($switchResult === false) {
            $this->logResponder->log('Could not switch to selected branch.', 'error', 'DeployWorker');
            return false;
        }

        // get revision of local repository:
        $localRevision = $this->deployDomain->getLocalRevision($repoPath, $deploymentData['branch'], $this->gitDomain);
        if (empty($localRevision)) {
            $this->logResponder->log('Could not estimate revision of local repository.', 'error', 'DeployWorker');
            return false;
        }
        $this->logResponder->log('Local repository is at revision: ' . $localRevision, 'default', 'DeployWorker');

        // stop processing if remote server is up to date:
        if ($localRevision === $remoteRevision && $this->listOnly === false) {
            $this->logResponder->log('Remote server is up to date.', 'info', 'DeployWorker');
            return true;
        }

        // collect file changes:
        $this->logResponder->log('Collecting file changes...', 'default', 'DeployWorker');
        if ($this->listOnly === false) {
            $changedFiles = $this->deployDomain->getChangedFiles(
                $repoPath,
                $localRevision,
                $remoteRevision,
                $this->gitDomain
            );
            if (empty($changedFiles)) {
                $this->logResponder->log('Could not estimate changed files.', 'error', 'DeployWorker');
                return false;
            }
        } else {
            // if in list-only mode return list of changed files:
            $changedFiles = $this->deployDomain->getChangedFilesForList(
                $repoPath,
                $localRevision,
                $remoteRevision,
                $this->gitDomain
            );
            if (empty($changedFiles)) {
                $this->logResponder->log('No changed files found.', 'info', 'DeployWorker');
            }
            $this->changedFilesResponder->respond($changedFiles);
            return true;
        }

        $uploadCount = count($changedFiles['upload']);
        $deleteCount = count($changedFiles['delete']);
        if ($uploadCount === 0 && $deleteCount === 0) {
            $this->logResponder->log('Noting to upload or delete.', 'info', 'DeployWorker');
            return true;
        }
        $this->logResponder->log(
            'Diff complete. (Files to upload: '.$uploadCount.' - Files to delete: ' . $deleteCount . ')',
            'default',
            'DeployWorker'
        );

        // Start upload/delete process:
        $repoPath = rtrim($repoPath, '/') . '/';
        if ($uploadCount > 0) {
            $this->logResponder->log('Starting file uploads...', 'default', 'DeployWorker');
            foreach ($changedFiles['upload'] as $file) {
                $uploadStart = microtime(true);
                $result = $this->server->upload($repoPath.$file, $remotePath.$file);
                $uploadEnd = microtime(true);
                $uploadDuration = round($uploadEnd - $uploadStart, 2);
                if ($result === true) {
                    $this->logResponder->log(
                        'Uploading ' . $file . ': success ('.$uploadDuration.'s)',
                        'info',
                        'DeployWorker'
                    );
                } else {
                    $this->logResponder->log('Uploading ' . $file . ': failed', 'danger', 'DeployWorker');
                }
            }
        }
        if ($deleteCount > 0) {
            $this->logResponder->log('Removing files...', 'default', 'DeployWorker');
            foreach ($changedFiles['delete'] as $file) {
                $result = $this->server->delete($remotePath.$file);
                if ($result === true) {
                    $this->logResponder->log('Deleting ' . $file . ': success', 'info', 'DeployWorker');
                } else {
                    $this->logResponder->log('Deleting ' . $file . ': failed', 'danger', 'DeployWorker');
                }
            }
        }

        // Update remote revision file:
        if ($this->server->putContent($localRevision, $remotePath.'REVISION') === false) {
            $this->logResponder->log('Could not update remote revision file.', 'error', 'DeployWorker');
            return false;
        } else {
            $this->logResponder->log('Revision file successfully updated.', 'default', 'DeployWorker');
        }

        return true;
    }
}
