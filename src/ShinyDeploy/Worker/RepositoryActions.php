<?php namespace ShinyDeploy\Worker;

use ShinyDeploy\Action\CloneRepository;
use ShinyDeploy\Action\DeleteRepositoryFiles;
use ShinyDeploy\Core\Worker;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Exceptions\WorkerException;

class RepositoryActions extends Worker
{
    /**
     * Calls all "init methods" and waits for jobs from gearman server.
     */
    protected function startup()
    {
        $this->GearmanWorker->addFunction('cloneRepository', [$this, 'cloneRepository']);
        $this->GearmanWorker->addFunction('deleteRepository', [$this, 'deleteRepository']);
        while ($this->GearmanWorker->work()) {
            // wait for jobs
        }
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
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['repositoryId'])) {
                throw new WebsocketException('Can not handle job. No repository-id provided.');
            }
            $cloneRepositoryAction = new CloneRepository($this->config, $this->logger);
            $cloneRepositoryAction->__invoke($params['repositoryId'], $params['clientId']);
        } catch (WorkerException $e) {
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
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['repoPath'])) {
                throw new WebsocketException('Can not handle job. No repository path provided.');
            }
            $deleteRepositoryAction = new DeleteRepositoryFiles($this->config, $this->logger);
            $deleteRepositoryAction->__invoke($params['repoPath'], $params['clientId']);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}