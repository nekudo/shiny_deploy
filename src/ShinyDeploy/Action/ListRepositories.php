<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\RepositoriesResponder;

class ListRepositories extends Action
{
    public function __invoke()
    {
        $serversResponder = new RepositoriesResponder($this->config, $this->logger, $this->slim);
        $serversResponder->index();
    }
}
