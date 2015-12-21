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
     */
    public function log($message)
    {
        $this->pushMessage($message, 'default');
    }

    /**
     * Sends a log message of type "success".
     *
     * @param string $message
     */
    public function success($message)
    {
        $this->pushMessage($message, 'success');
    }

    /**
     * Sends a log message of type "info".
     *
     * @param string $message
     */
    public function info($message)
    {
        $this->pushMessage($message, 'info');
    }

    /**
     * Sends a log message of type "danger".
     *
     * @param string $message
     */
    public function danger($message)
    {
        $this->pushMessage($message, 'danger');
    }

    /**
     * Sends a log message of type "error".
     *
     * @param string $message
     * @param string $source
     */
    public function error($message)
    {
        $this->pushMessage($message, 'error');
    }

    /**
     * Sends a log event to websocket server.
     *
     * @param string $msg
     * @param string $type
     * @param string $source
     * @throws \RuntimeException
     */
    protected function pushMessage($msg, $type = 'default')
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
