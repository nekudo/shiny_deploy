<?php namespace ShinyDeploy\Worker;

require __DIR__ . '/../../../vendor/autoload.php';

use ShinyDeploy\Action\WsWorkerAction\CloneRepository;
use ShinyDeploy\Action\WsWorkerAction\DeleteRepositoryFiles;
use ShinyDeploy\Core\Worker;
use ShinyDeploy\Exceptions\MissingDataException;

class RepositoryActions extends Worker
{
    /**
     * Calls all "init methods" and waits for jobs from gearman server.
     */
    protected function registerCallbacks()
    {
        $this->GearmanWorker->addFunction('cloneRepository', [$this, 'cloneRepository']);
        $this->GearmanWorker->addFunction('deleteRepository', [$this, 'deleteRepository']);
    }

    /**
     * Clones a repository
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function cloneRepository(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            if (empty($params['repositoryId'])) {
                throw new MissingDataException('RepositoryId can not be empty.');
            }
            $cloneRepositoryAction = new CloneRepository($this->config, $this->logger);
            $cloneRepositoryAction->setClientId($params['clientId']);
            $cloneRepositoryAction->setToken($params['token']);
            $cloneRepositoryAction->__invoke($params['repositoryId'], $params['clientId']);
        } catch (\Exception $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }

    /**
     * Physically removes a repository
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function deleteRepository(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            if (empty($params['repoPath'])) {
                throw new MissingDataException('RepoPath can not be empty.');
            }
            $deleteRepositoryAction = new DeleteRepositoryFiles($this->config, $this->logger);
            $deleteRepositoryAction->setClientId($params['clientId']);
            $deleteRepositoryAction->setToken($params['token']);
            $deleteRepositoryAction->__invoke($params['repoPath']);
        } catch (\Exception $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}
