<?php
namespace ShinyDeploy\Action;

class Dummy
{
    public function __invoke($params)
    {
        echo 'dummy action' . PHP_EOL;
        print_r($params);
    }
}
