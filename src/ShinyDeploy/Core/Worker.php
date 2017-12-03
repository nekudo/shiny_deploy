<?php namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Apix\Log\Logger\File;
use Nekudo\ShinyGears\Worker as ShinyGearsWorker;
use Noodlehaus\Config;

abstract class Worker extends ShinyGearsWorker
{
    /** @var Config Project config. */
    protected $config;

    /** @var  Logger $logger */
    protected $logger;

    /** @var  \ZMQContext $zmqContext */
    protected $zmqContext;

    public function __construct(array $config, string $poolName, string $workerName)
    {
        // load config:
        $this->config = Config::load(__DIR__ . '/../../../config/config.php');

        // init logger:
        $this->logger = new Logger;
        $fileLogger = new File($this->config->get('logging.file'));
        $fileLogger->setMinLevel($this->config->get('logging.level'));
        $this->logger->add($fileLogger);

        $this->zmqContext = new \ZMQContext;
        $this->logger->info('Starting worker. (Name: ' . $workerName . ')');

        parent::__construct($config, $poolName, $workerName);
    }
}
