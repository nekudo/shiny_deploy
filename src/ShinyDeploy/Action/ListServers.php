<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\ServersResponder;

class ListServers extends Action
{
    public function __invoke()
    {
        $serversResponder = new ServersResponder($this->config, $this->logger, $this->slim);
        $serversResponder->index();
    }
}
