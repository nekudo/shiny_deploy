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
     * Sends a log event to websocket server.
     *
     * @param string $msg
     * @param string $type
     * @param string $source
     * @throws \RuntimeException
     */
    public function log($msg, $type = 'default', $source = '')
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }
        $msg = nl2br($msg);
        $pushData = [
            'clientId' => $this->clientId,
            'eventName' => 'log',
            'eventPayload' => [
                'source' => $source,
                'type' => $type,
                'text' => $msg,
            ],
        ];
        $this->zmqSend($pushData);
    }
}
