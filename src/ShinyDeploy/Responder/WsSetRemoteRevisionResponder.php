<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;

class WsSetRemoteRevisionResponder extends WsEventResponder
{
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Set remote revision.
     *
     * @param string $revision
     * @return void
     * @throws \RuntimeException
     * @throws \ZMQException
     */
    public function respond(string $revision) : void
    {
        if (empty($this->clientId)) {
            throw new \RuntimeException('Client-Id not set.');
        }

        $pushData = [
            'clientId' => $this->clientId,
            'eventName' => 'setRemoteRevision',
            'eventPayload' => [
                'revision' => $revision
            ],
        ];

        $this->zmqSend($pushData);
    }
}
