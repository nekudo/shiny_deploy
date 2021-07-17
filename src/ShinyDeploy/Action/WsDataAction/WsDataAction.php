<?php

namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Action\WsAction;
use ShinyDeploy\Responder\WsDataResponder;

abstract class WsDataAction extends WsAction
{
    /** @var WsDataResponder $responder */
    protected WsDataResponder $responder;

    /**
     * @param array $actionPayload
     * @return bool
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     */
    abstract public function __invoke(array $actionPayload): bool;

    /**
     * Sets a responder.
     *
     * @param WsDataResponder $responder
     * @return void
     */
    public function setResponder(WsDataResponder $responder): void
    {
        $this->responder = $responder;
    }

    /**
     * Fetches response for given callback.
     *
     * @param int $callbackId
     * @return array
     */
    public function getResponse(int $callbackId): array
    {
        $this->responder->setCallbackId($callbackId);
        return $this->responder->getFrameData();
    }
}
