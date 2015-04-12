<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;

class GetServers extends Action
{
    public function __invoke()
    {

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
