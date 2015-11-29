<?php
namespace ShinyDeploy\Domain;

use RuntimeException;
use ShinyDeploy\Core\Domain;
use ShinyDeploy\Core\Responder;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Domain\Database\Servers;
use ShinyDeploy\Domain\Server\Server;

class Deployment extends Domain
{
    /** @var Server $server */
    protected $server;

    /** @var Repository $repository */
    protected $repository;

    /** @var LogResponder $logResponder */
    protected $logResponder;

    /** @var array $changedFiles */
    protected $changedFiles = [];

    public function init(array $data, $encryptionKey)
    {
        $this->data = $data;
        $servers = new Servers($this->config, $this->logger);
        $servers->setEnryptionKey($encryptionKey);
        $repositories = new Repositories($this->config, $this->logger);
        $repositories->setEnryptionKey($encryptionKey);
        $this->server = $servers->getServer($data['server_id']);
        $this->repository = $repositories->getRepository($data['repository_id']);
    }

    /**
     * Setter for the websocket log repsonder.
     *
     * @param Responder $logResponder
     */
    public function setLogResponder(Responder $logResponder)
    {
        $this->logResponder = $logResponder;
    }

    /**
     * Returns list of changed files.
     *
     * @return array
     */
    public function getChangedFiles()
    {
        return $this->changedFiles;
    }

    /**
     * Executes an actual deployment.
     *
     * @param bool $listMode If true changed files are only listed but not acutually deployed.
     * @throws RuntimeException
     * @return bool
     */
    public function deploy($listMode = false)
    {
        if (empty($this->data)) {
            throw new RuntimeException('Deployment data not found. Initialization missing?');
        }
        if (empty($this->server)) {
            throw new RuntimeException('Server object not found.');
        }
        if (empty($this->repository)) {
            throw new RuntimeException('Repository object not found');
        }

        $this->logResponder->log('Checking prerequisites...', 'default', 'DeployService');
        if ($this->checkPrerequisites() === false) {
            $this->logResponder->log('Prerequisites check failed. Aborting job.', 'error', 'DeployService');
            return false;
        }

        $this->logResponder->log('Preparing local repository...', 'default', 'DeployService');
        if ($this->prepareRepository() === false) {
            $this->logResponder->log('Preparation of local repository failed. Aborting job.', 'error', 'DeployService');
            return false;
        }


        if ($listMode === false) {
            $this->logResponder->log('Running tasks...', 'default', 'DeployService');
            if ($this->runTasks('before') === false) {
                $this->logResponder->log('Running tasks failed. Aborting job.', 'error', 'DeployService');
                return false;
            }
        }

        $this->logResponder->log('Estimating remote revision...', 'default', 'DeployService');
        $remoteRevision = $this->getRemoteRevision();
        if ($remoteRevision === false) {
            $this->logResponder->log('Could not estimate remote revision. Aborting job.', 'error', 'DeployService');
            return false;
        }

        $this->logResponder->log('Switching branch...', 'default', 'DeployService');
        if ($this->switchBranch() === false) {
            $this->logResponder->log('Could not swtich to selected branch. Aborting job.', 'error', 'DeployService');
            return false;
        }

        $this->logResponder->log('Estimating local revision...', 'default', 'DeployService');
        $localRevision = $this->getLocalRevision();
        if ($localRevision === false) {
            $this->logResponder->log('Could not estimate local revision. Aborting job.', 'error', 'DeployService');
            return false;
        }

        // If remote server is up to date we can stop right here:
        if ($localRevision === $remoteRevision) {
            $this->logResponder->log('Remote server is aleady up to date.', 'info', 'DeployService');
            return true;
        }

        $this->logResponder->log('Collecting changed files...', 'default', 'DeployService');
        $changedFiles = $this->getChangedFilesList($localRevision, $remoteRevision);
        if (empty($changedFiles)) {
            $this->logResponder->log('Could not estimate changed files.', 'error', 'DeployService');
            return false;
        }

        // If we are in list mode we can now respond with the list of changed files:
        if ($listMode === true) {
            $this->changedFiles = $changedFiles;
            return true;
        }

        $this->logResponder->log('Sorting changed files...', 'default', 'DeployService');
        $sortedChangedFiles = $this->sortFilesByOperation($changedFiles);

        $this->logResponder->log('Processing changed files...', 'default', 'DeployService');
        if ($this->processChangedFiles($sortedChangedFiles) === false) {
            $this->logResponder->log('Could not process files. Aborting job.', 'error', 'DeployService');
            return false;
        }

        $this->logResponder->log('Updating revision file...', 'default', 'DeployService');
        if ($this->updateRemoteRevisionFile($localRevision) === false) {
            $this->logResponder->log('Could not update remove revision file. Aborting job.', 'error', 'DeployService');
            return false;
        }

        $this->logResponder->log('Running tasks...', 'default', 'DeployService');
        if ($listMode === false && $this->runTasks('after') === false) {
            $this->logResponder->log('Running tasks failed. Aborting job.', 'error', 'Deployment');
            return false;
        }

        return true;
    }

    /**
     * Checks various requirements to be fulfilled before stating a deployment.
     *
     * @return boolean
     */
    protected function checkPrerequisites()
    {
        $this->logResponder->log('Checking git binary...', 'default', 'PrerequisitesCheck');
        if ($this->repository->checkGit() === false) {
            $this->logResponder->log('Git executable not found. Aborting job.', 'danger', 'PrerequisitesCheck');
            return false;
        }
        $this->logResponder->log('Checking connection to repository...', 'default', 'PrerequisitesCheck');
        if ($this->repository->checkConnectivity() === false) {
            $this->logResponder->log('Connection to repository failed.', 'danger', 'PrerequisitesCheck');
            return false;
        }
        $this->logResponder->log('Checking connection target server...', 'default', 'PrerequisitesCheck');
        if ($this->server->checkConnectivity() === false) {
            $this->logResponder->log('Connection to remote server failed.', 'danger', 'PrerequisitesCheck');
            return false;
        }
        return true;
    }

    /**
     * If local repository does not exist it will be pulled from git. It it exists it will be updated.
     *
     * @return bool
     */
    protected function prepareRepository()
    {
        if ($this->repository->exists() === true) {
            $result = $this->repository->doPull();
            if ($result === false) {
                $this->logResponder->log('Error while updating repository.', 'danger', 'prepareRepository');
            }
        } else {
            $result = $this->repository->doClone();
            if ($result === false) {
                $this->logResponder->log('Error while cloning repository.', 'danger', 'prepareRepository');
            }
        }
        return $result;
    }

    /**
     * Runs user defined tasks on target server.
     *
     * @param string $type
     * @return boolean
     */
    protected function runTasks($type)
    {
        // Skip if no tasks defined
        if (empty($this->data['tasks'])) {
            return true;
        }

        // Skip if no tasks of given type defined:
        $typeTasks = [];
        foreach ($this->data['tasks'] as $task) {
            if ($task['type'] === $type) {
                array_push($typeTasks, $task);
            }
        }
        if (empty($typeTasks)) {
            return true;
        }

        // Skip if server is not ssh capable:
        if ($this->server->getType() !== 'ssh') {
            $this->logResponder->log('Server not of type SSH. Skipping tasks.', 'danger', 'runTasks');
            return false;
        }

        // Execute tasks on server:
        $remotePath = $this->getRemotePath();
        foreach ($typeTasks as $task) {
            $command = 'cd ' . $remotePath . ' && ' . $task['command'];
            $this->logResponder->log('Executing task: ' . $task['name'], 'info', 'runTasks');
            $response = $this->server->executeCommand($command);
            if ($response === false) {
                $this->logResponder->log('Task failed.', 'danger', 'runTasks');
            } else {
                $this->logResponder->log($response, 'default', 'runTasks');
            }
        }
        return true;
    }

    /**
     * Get the deployment path on target server.
     *
     * @return string
     */
    protected function getRemotePath()
    {
        $serverRoot = $this->server->getRootPath();
        $serverRoot = rtrim($serverRoot, '/');
        $targetPath = trim($this->data['target_path']);
        $targetPath = trim($targetPath, '/');
        $remotePath = $serverRoot . '/' . $targetPath . '/';
        return $remotePath;
    }

    /**
     * Fetches remote revision from REVISION file in project root.
     *
     * @return string|bool
     */
    public function getRemoteRevision()
    {
        $targetPath = $this->getRemotePath();
        $targetPath .= 'REVISION';
        $revision = $this->server->getFileContent($targetPath);
        $revision = trim($revision);
        if (!empty($revision) && preg_match('#[0-9a-f]{40}#', $revision) === 1) {
            $this->logResponder->log('Remote server is at revision: ' . $revision, 'info', 'getRemoteRevision');
            return $revision;
        }
        $targetDir = dirname($targetPath);
        $targetDirContent = $this->server->listDir($targetDir);
        if ($targetDirContent === false) {
            return false;
        }
        if (is_array($targetDirContent) && empty($targetDirContent)) {
             $this->logResponder->log('Remote revision not found - deploying all files.', 'info', 'getRemoteRevision');
            return '-1';
        }
        return false;
    }

    /**
     * Fetches revision of local repository.
     *
     * @return bool|string
     */
    public function getLocalRevision()
    {
        if ($this->repository->checkConnectivity() === false) {
            $this->logResponder->log('Could not connect to remote repository.', 'info', 'getLocalRevision');
            return false;
        }
        $revision = $this->repository->getRemoteRevision($this->data['branch']);
        if ($revision !== false) {
            $this->logResponder->log('Local repository is at revision: ' . $revision, 'info', 'getLocalRevision');
        } else {
            $this->logResponder->log('Local revision not found.', 'info', 'getLocalRevision');
        }
        return $revision;
    }

    /**
     * Switch repository to deployment branch.
     *
     * @return bool
     */
    protected function switchBranch()
    {
        return $this->repository->switchBranch($this->data['branch']);
    }

    /**
     * Generates list with changed,added,deleted files.
     *
     * @param string $localRevision
     * @param string $remoteRevision
     * @return bool|array
     */
    protected function getChangedFilesList($localRevision, $remoteRevision)
    {
        if ($remoteRevision === '-1') {
            $changedFiles = $this->repository->listFiles();
        } else {
            $changedFiles = $this->repository->getDiff($localRevision, $remoteRevision);
        }
        if (empty($changedFiles)) {
            return false;
        }

        $files = [];
        if ($remoteRevision === '-1') {
            foreach ($changedFiles as $file) {
                $item = [
                    'type' => 'A',
                    'file' => $file,
                    'diff' => '',
                ];
                array_push($files, $item);
            }
        } else {
            $files = $changedFiles;
        }

        return $files;
    }

    /**
     * Sort files by opration to do (e.g. upload, delete, ...)
     * @param array $files
     * @return array
     */
    protected function sortFilesByOperation($files)
    {
        $sortedFiles = [
            'upload' => [],
            'delete' => [],
        ];
        foreach ($files as $item) {
            if (in_array($item['type'], ['A', 'C', 'M', 'R'])) {
                $sortedFiles['upload'][] = $item['file'];
            } elseif ($item['type'] === 'D') {
                $sortedFiles['delete'][] = $item['file'];
            }
        }
        return $sortedFiles;
    }

    /**
     * Deploys changes to target server by uploading/deleting files.
     *
     * @param array $changedFiles
     */
    protected function processChangedFiles($changedFiles)
    {
        $repoPath = $this->repository->getLocalPath();
        $repoPath = rtrim($repoPath, '/') . '/';
        $remotePath = $this->getRemotePath();
        $uploadCount = count($changedFiles['upload']);
        $deleteCount = count($changedFiles['delete']);
        if ($uploadCount === 0 && $deleteCount === 0) {
            $this->logResponder->log('Noting to upload or delete.', 'info', 'processChangedFiles');
            return true;
        }
        $this->logResponder->log(
            'Files to upload: '.$uploadCount.' - Files to delete: ' . $deleteCount . ' - processing...',
            'info',
            'processChangedFiles'
        );

        if ($uploadCount > 0) {
            foreach ($changedFiles['upload'] as $file) {
                $uploadStart = microtime(true);
                $result = $this->server->upload($repoPath.$file, $remotePath.$file);
                $uploadEnd = microtime(true);
                $uploadDuration = round($uploadEnd - $uploadStart, 2);
                if ($result === true) {
                    $this->logResponder->log(
                        'Uploading ' . $file . ': success ('.$uploadDuration.'s)',
                        'info',
                        'processChangedFiles'
                    );
                } else {
                    $this->logResponder->log('Uploading ' . $file . ': failed', 'danger', 'processChangedFiles');
                }
            }
        }
        if ($deleteCount > 0) {
            $this->logResponder->log('Removing files...', 'default', 'Deployment');
            foreach ($changedFiles['delete'] as $file) {
                $result = $this->server->delete($remotePath.$file);
                if ($result === true) {
                    $this->logResponder->log('Deleting ' . $file . ': success', 'info', 'processChangedFiles');
                } else {
                    $this->logResponder->log('Deleting ' . $file . ': failed', 'danger', 'processChangedFiles');
                }
            }
        }

        $this->logResponder->log('Processing files completed.', 'info', 'processChangedFiles');

        return true;
    }

    /**
     * Updates revision file on remote server.
     *
     * @return boolean
     */
    protected function updateRemoteRevisionFile($revision)
    {
        $remotePath = $this->getRemotePath();
        if ($this->server->putContent($revision, $remotePath.'REVISION') === false) {
            $this->logResponder->log('Could not update remote revision file.', 'error', 'updateRemoteRevisionFile');
            return false;
        }
        return true;
    }
}
