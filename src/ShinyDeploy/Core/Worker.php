<?php namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Apix\Log\Logger\File;
use Nekudo\Angela\Worker as AngelaBaseWorker;
use Noodlehaus\Config;
use ShinyDeploy\Exceptions\MissingDataException;

abstract class Worker extends AngelaBaseWorker
{
    /** @var Config Project config. */
    protected $config;

    /** @var  Logger $logger */
    protected $logger;

    /** @var  \ZMQContext $zmqContext */
    protected $zmqContext;

    public function __construct($workerName, $gearmanHost, $gearmanPort, $runPath)
    {
        // load config:
        $this->config = Config::load(__DIR__ . '/../config.php');

        // init logger:
        $this->logger = new Logger;
        $fileLogger = new File($this->config->get('logging.file'));
        $fileLogger->setMinLevel($this->config->get('logging.level'));
        $this->logger->add($fileLogger);

        $this->zmqContext = new \ZMQContext;
        $this->logger->info('Starting worker. (Name: ' . $workerName . ')');
        parent::__construct($workerName, $gearmanHost, $gearmanPort, $runPath);
    }

    /**
     * Sends a log message to websocket server.
     *
     * @param string $clientId Unique identifier for client.
     * @param string $msg
     * @param string $type
     * @throws MissingDataException
     */
    protected function wsLog($clientId, $msg, $type = 'default')
    {
        if (empty($clientId)) {
            throw new MissingDataException('ClientId can not be empty.');
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
