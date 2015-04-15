<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Domain\Servers;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Responder\WsDataResponder;

class AddServer extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        $responder = new WsDataResponder($this->config, $this->logger);
        if (!isset($actionPayload['serverData'])) {
            throw new WebsocketException('Invalid addServer request received.');
        }
        $serverData = $actionPayload['serverData'];

        // @todo Implement server side data validation!

        $serversDomain = new Servers($this->config, $this->logger);
        $addResult = $serversDomain->addServer($serverData);
        if ($addResult === false) {
            $responder->setError('testing error response...');
        }
        $this->setResponse($responder);
    }

    public function setResponse(WsDataResponder $responder)
    {
        $this->responder = $responder;
    }
}
