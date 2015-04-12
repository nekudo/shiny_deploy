<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Servers;

class AddServer extends Action
{
    public function __invoke()
    {
        $serversDomain = new Servers($this->config, $this->logger);
        //$servers = $serversDomain->getServers();
        return [
            'type' => 'error',
        ];
    }
}
