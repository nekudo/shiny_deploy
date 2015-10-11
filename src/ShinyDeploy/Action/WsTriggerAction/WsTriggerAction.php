<?php
namespace ShinyDeploy\Action\WsTriggerAction;

use ShinyDeploy\Core\Action;

abstract class WsTriggerAction extends Action
{
    protected $clientId;

    abstract public function __invoke(array $actionPayload);

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
}
