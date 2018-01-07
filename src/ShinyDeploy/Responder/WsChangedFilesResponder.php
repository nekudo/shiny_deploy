<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;

class WsChangedFilesResponder extends WsEventResponder
{
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Sends list of changed files to websocket server.
     *
     * @param array $changedFiles
     * @return void
     * @throws \RuntimeException
     * @throws \ZMQException
     */
    public function respond(array $changedFiles) : void
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }

        $pushData = [
            'clientId' => $this->clientId,
            'eventName' => 'updateChangedFiles',
            'eventPayload' => [
                'changedFiles' => $changedFiles
            ],
        ];

        $this->zmqSend($pushData);
    }
}
