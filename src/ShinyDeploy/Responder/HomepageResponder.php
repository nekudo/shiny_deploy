<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\SlimResponder;

class HomepageResponder extends SlimResponder
{
    public function home()
    {
        $this->display('home.php');
    }
}
