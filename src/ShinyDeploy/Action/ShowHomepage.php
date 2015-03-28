<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\HomepageResponder;

class ShowHomepage extends Action
{
    public function __invoke()
    {
        $homepageResponder = new HomepageResponder($this->config, $this->logger, $this->slim);
        $homepageResponder->home();
    }
}
