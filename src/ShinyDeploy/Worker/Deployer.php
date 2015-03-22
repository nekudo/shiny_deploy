<?php namespace ShinyDeploy\Worker;

use ShinyDeploy\Action\Deploy;
use ShinyDeploy\Core\Worker;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Exceptions\WorkerException;

class Deployer extends Worker
{
    /**
     * Calls all "init methods" and waits for jobs from gearman server.
     */
    protected function startup()
    {
        $this->GearmanWorker->addFunction('deploy', [$this, 'deploy']);
        while ($this->GearmanWorker->work()) {
            // wait for jobs
        }
    }

    /**
     * Handles deployment related actions.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function deploy(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            $clientId = (!empty($params['clientId'])) ? $params['clientId'] : false;
            $sourceId = (!empty($params['idSource'])) ? $params['idSource'] : false;
            $targetId = (!empty($params['idTarget'])) ? $params['idTarget'] : false;
            if (empty($clientId)) {
                throw new WebsocketException('Can not handle job. No uuid provided.');
            }
            if (empty($sourceId) || empty($targetId)) {
                $this->wsLog($clientId, 'No source or target defined. Aborting job.', 'error');
                return true;
            }
            $deployAction = new Deploy($this->config, $this->logger);
            $deployAction->__invoke($clientId, $sourceId, $targetId);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}
