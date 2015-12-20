<?php
namespace ShinyDeploy\Action\WsDataAction;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\WsDataResponder;

abstract class WsDataAction extends Action
{
    /** @var WsDataResponder $responder */
    protected $responder;

    protected $clientId;

    abstract public function __invoke(array $actionPayload);

    public function __construct(Config $config, Logger $logger) {
        parent::__construct($config, $logger);
        $this->responder = new WsDataResponder($this->config, $this->logger);
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
