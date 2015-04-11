<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\ServersResponder;

class GetServers extends Action
{
    public function __invoke()
    {
        //$serversResponder = new ServersResponder($this->config, $this->logger, $this->slim);
        //$serversResponder->index();



        return [
            0 => [
                'id' => 1,
                'name' => 'foo',
            ],
            1 => [
                'id' => 2,
                'name' => 'bar',
            ],
        ];
    }
}
