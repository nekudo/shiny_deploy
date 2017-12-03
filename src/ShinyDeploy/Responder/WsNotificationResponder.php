<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;

class WsNotificationResponder extends WsEventResponder
{
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Sends a log event to websocket server.
     *
     * @param string $msg
     * @param string $type
     * @throws \RuntimeException
     * @throws \ZMQException
     * @return void
     */
    public function send(string $msg, string $type = 'default') : void
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }
        $msg = nl2br($msg);
        $pushData = [
            'clientId' => $this->clientId,
            'eventName' => 'notification',
            'eventPayload' => [
                'type' => $type,
                'text' => $msg,
            ],
        ];
        $this->zmqSend($pushData);
    }
}
