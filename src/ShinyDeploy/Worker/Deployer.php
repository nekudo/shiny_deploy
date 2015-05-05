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
            if (empty($params['clientId'])) {
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['deploymentId'])) {
                throw new WebsocketException('Can not handle job. No deployment-id provided.');
            }
            $deployAction = new Deploy($this->config, $this->logger);
            $deployAction->__invoke($params['deploymentId'], $params['clientId']);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}
