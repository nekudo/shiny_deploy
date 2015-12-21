<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Action\WsAction;
use ShinyDeploy\Core\Responder;
use ShinyDeploy\Responder\WsDataResponder;

abstract class WsDataAction extends WsAction
{
    /** @var WsDataResponder $responder */
    protected $responder;

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
}
