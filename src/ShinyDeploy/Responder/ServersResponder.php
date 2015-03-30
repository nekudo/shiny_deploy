<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\SlimResponder;

class ServersResponder extends SlimResponder
{
    public function index()
    {
        $this->display('servers.php');
    }
}
