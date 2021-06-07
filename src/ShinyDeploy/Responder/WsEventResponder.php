<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Core\Responder;

class WsEventResponder extends Responder
{
    /** @var  \ZMQContext $zmqContext */
    protected \ZMQContext $zmqContext;

    protected string $clientId;

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
    public function setClientId(string $clientId): bool
    {
        if (empty($clientId)) {
            return false;
        }
        $this->clientId = $clientId;
        return true;
    }

    /**
     * Sends a message to using zqm.
     *
     * @param array $data
     * @throws \ZMQException
     */
    protected function zmqSend(array $data): void
    {
        $zmqDsn = $this->config->get('zmq.dsn');
        $zmqSocket = $this->zmqContext->getSocket(\ZMQ::SOCKET_PUSH);
        $zmqSocket->connect($zmqDsn);
        $zmqSocket->send(json_encode($data));
        if (method_exists($zmqSocket, 'disconnect')) {
            $zmqSocket->disconnect($zmqDsn);
        }
    }
}
