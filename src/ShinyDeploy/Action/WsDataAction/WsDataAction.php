<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Action\WsAction;
use ShinyDeploy\Core\Responder;
use ShinyDeploy\Responder\WsDataResponder;

abstract class WsDataAction extends WsAction
{
    /** @var WsDataResponder $responder */
    protected $responder;

    /**
     * @param array $actionPayload
     * @return bool
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     */
    abstract public function __invoke(array $actionPayload) : bool;

    /**
     * Sets a responder.
     *
     * @param Responder $responder
     * @return void
     */
    public function setResponder(Responder $responder) : void
    {
        $this->responder = $responder;
    }

    /**
     * Fetches response for given callback.
     *
     * @param int $callbackId
     * @return array
     */
    public function getResponse(int $callbackId) : array
    {
        $this->responder->setCallbackId($callbackId);
        return $this->responder->getFrameData();
    }
}
