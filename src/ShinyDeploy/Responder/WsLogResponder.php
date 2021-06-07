<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;

class WsLogResponder extends WsEventResponder
{
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Sends a log message of type "default".
     *
     * @param string $message
     * @return void
     * @throws \ZMQException
     */
    public function log(string $message): void
    {
        $this->pushMessage($message, 'default');
    }

    /**
     * Sends a log message of type "success".
     *
     * @param string $message
     * @return void
     * @throws \ZMQException
     */
    public function success(string $message): void
    {
        $this->pushMessage($message, 'success');
    }

    /**
     * Sends a log message of type "info".
     *
     * @param string $message
     * @return void
     * @throws \ZMQException
     */
    public function info(string $message): void
    {
        $this->pushMessage($message, 'info');
    }

    /**
     * Sends a log message of type "danger".
     *
     * @param string $message
     * @return void
     * @throws \ZMQException
     */
    public function danger(string $message): void
    {
        $this->pushMessage($message, 'danger');
    }

    /**
     * Sends a log message of type "error".
     *
     * @param string $message
     * @return void
     * @throws \ZMQException
     */
    public function error(string $message): void
    {
        $this->pushMessage($message, 'error');
    }

    /**
     * Sends a log event to websocket server.
     *
     * @param string $msg
     * @param string $type
     * @return void
     * @throws \RuntimeException
     * @throws \ZMQException
     */
    protected function pushMessage(string $msg, string $type = 'default'): void
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }
        $pushData = [
            'clientId' => $this->clientId,
            'eventName' => 'log',
            'eventPayload' => [
                'type' => $type,
                'text' => $msg,
            ],
        ];
        $this->zmqSend($pushData);
    }
}
