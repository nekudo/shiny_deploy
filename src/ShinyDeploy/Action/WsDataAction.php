<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\WsDataResponder;

abstract class WsDataAction extends Action
{
    /** @var WsDataResponder $responder */
    protected $responder;

    protected $clientId;

    abstract public function __invoke($actionPayload);

    abstract public function setResponse(WsDataResponder $responder);

    public function getResponse($callbackId)
    {
        $this->responder->setCallbackId($callbackId);
        return $this->responder->getFrameData();
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
}
