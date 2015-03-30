<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\SlimResponder;

class RepositoriesResponder extends SlimResponder
{
    public function index()
    {
        $this->display('repositories.php');
    }
}
