<?php namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Exceptions\WorkerException;

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

    /** @var  \ZMQContext $zmqContext */
    protected $zmqContext;

    abstract protected function registerCallbacks();

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

        $this->zmqContext = new \ZMQContext;

        // Register methods every worker has:
        $this->GearmanWorker->addFunction('ping_' . $this->workerName, array($this, 'ping'));
        $this->GearmanWorker->addFunction('jobinfo_' . $this->workerName, array($this, 'getJobInfo'));
        $this->GearmanWorker->addFunction('pidupdate_' . $this->workerName, array($this, 'updatePidFile'));

        $this->registerCallbacks();

        $this->logger->info('Starting worker. (Name: ' . $workerName . ')');

        // Let's roll...
        $this->startup();
    }

    /**
     * Startup worker and wait for jobs.
     */
    protected function startup()
    {
        $this->updatePidFile();
        while ($this->GearmanWorker->work());
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

    /**
     * Updates PID file for the worker.
     *
     * @return bool
     * @throws WebsocketException
     */
    public function updatePidFile()
    {
        $pidPath = $this->config->get('gearman.pidPath') . $this->workerName . '.pid';
        if (file_put_contents($pidPath, time()) === false) {
            throw new WebsocketException('Could not create PID file.');
        }
        return true;
    }

    /**
     * Sends a log message to websocket server.
     *
     * @param string $clientId Unique identifier for client.
     * @param string $msg
     * @param string $type
     * @throws WorkerException
     */
    protected function wsLog($clientId, $msg, $type = 'default')
    {
        if (empty($clientId)) {
            throw new WorkerException('Required parameter missing.');
        }
        $pushData = [
            'clientId' => $clientId,
            'wsEventName' => 'log',
            'wsEventParams' => [
                'source' => $this->workerName,
                'type' => $type,
                'text' => $msg,
            ],
        ];
        $this->zmqSend($pushData);
    }

    /**
     * Sends a message to using zqm.
     *
     * @param array $data
     */
    protected function zmqSend(array $data)
    {
        $zmqDsn = $this->config->get('zmq.dsn');
        $zmqSocket = $this->zmqContext->getSocket(\ZMQ::SOCKET_PUSH);
        $zmqSocket->connect($zmqDsn);
        $zmqSocket->send(json_encode($data));
        $zmqSocket->disconnect($zmqDsn);
    }
}
