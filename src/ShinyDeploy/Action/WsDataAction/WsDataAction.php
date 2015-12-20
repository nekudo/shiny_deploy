<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Core\Responder;
use ShinyDeploy\Responder\WsDataResponder;

abstract class WsDataAction extends Action
{
    /** @var WsDataResponder $responder */
    protected $responder;

    protected $clientId;

    abstract public function __invoke(array $actionPayload);

    public function setResponder(Responder $responder)
    {
        $this->responder = $responder;
    }

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
