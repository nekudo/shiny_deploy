<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;

class WsFileDiffResponder extends WsEventResponder
{
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Sends a file diff.
     *
     * @param string $diff
     * @throws \RuntimeException
     * @throws \ZMQException
     */
    public function respond(string $diff)
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }

        $pushData = [
            'clientId' => $this->clientId,
            'eventName' => 'showDiff',
            'eventPayload' => [
                'diff' => $diff
            ],
        ];

        $this->zmqSend($pushData);
    }
}
