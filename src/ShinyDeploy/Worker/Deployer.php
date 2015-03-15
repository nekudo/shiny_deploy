<?php namespace ShinyDeploy\Worker;

use ShinyDeploy\Core\Worker;
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

            // @todo do something...

        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}
