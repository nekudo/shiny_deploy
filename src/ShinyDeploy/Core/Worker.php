<?php namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;

abstract class Worker
{
    /** @var string Unique name to identify worker. */
    protected $workerName;

    /** @var \GearmanWorker */
    protected $GearmanWorker;

    /** @var int Jobs handled by worker since start. */
    protected $jobsTotal = 0;

    /** @var int Worker startup time. */
    protected $startupTime = 0;

    /** @var Config Project config. */
    protected $config;

    /** @var  Logger $logger */
    protected $logger;

    abstract protected function startup();

    public function __construct($workerName, Config $config, Logger $logger)
    {
        $this->workerName = $workerName;
        $this->config = $config;
        $this->logger = $logger;
        $this->startupTime = time();

        $this->GearmanWorker = new \GearmanWorker;
        $this->GearmanWorker->addServer(
            $this->config->get('gearman.host'),
            $this->config->get('gearman.port')
        );

        // Register methods every worker has:
        $this->GearmanWorker->addFunction('ping_' . $this->workerName, array($this, 'ping'));
        $this->GearmanWorker->addFunction('jobinfo_' . $this->workerName, array($this, 'getJobInfo'));

        $this->logger->info('Starting worker. (Name: ' . $workerName . ')');

        // register worker functions and wait for jobs:
        $this->startup();
    }

    public function __destruct()
    {

    }

    /**
     * Simple ping method to test if worker is alive.
     *
     * @param \GearmanJob $Job
     */
    public function ping($Job)
    {
        $Job->sendData('pong');
    }

    /**
     * Increases job counter.
     */
    public function countJob()
    {
        $this->jobsTotal++;
    }

    /**
     * Returns information about jobs handled.
     *
     * @param \GearmanJob $Job
     */
    public function getJobInfo($Job)
    {
        $uptimeSeconds = time() - $this->startupTime;
        $uptimeSeconds = ($uptimeSeconds === 0) ? 1 : $uptimeSeconds;
        $avgJobsMin = $this->jobsTotal / ($uptimeSeconds / 60);
        $avgJobsMin = round($avgJobsMin, 2);
        $response = [
            'jobs_total' => $this->jobsTotal,
            'avg_jobs_min' => $avgJobsMin,
            'uptime_seconds' => $uptimeSeconds,
        ];
        $Job->sendData(json_encode($response));
    }
}
