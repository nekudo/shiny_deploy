<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Guzzle\Common\Exception\RuntimeException;
use Noodlehaus\Config;
use ShinyDeploy\Core\Responder;

class WsGatewayResponder extends Responder
{
    /** @var  \ZMQContext $zmqContext */
    protected $zmqContext;

    protected $clientId;

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);

        $this->zmqContext = new \ZMQContext;
    }

    /**
     * Sets client id.
     *
     * @param string $clientId
     * @return bool
     */
    public function setClientId($clientId)
    {
        if (empty($clientId)) {
            return false;
        }
        $this->clientId = $clientId;
        return true;
    }

    /**
     * Sends a log message to websocket server.
     *
     * @param string $msg
     * @param string $type
     * @param string $source
     * @throws RuntimeException
     */
    public function log($msg, $type = 'default', $source = '')
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }
        $pushData = [
            'clientId' => $this->clientId,
            'wsEventName' => 'log',
            'wsEventParams' => [
                'source' => $source,
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
