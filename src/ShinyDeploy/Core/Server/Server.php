<?php
namespace ShinyDeploy\Core\Server;

abstract class Server
{
    abstract public function connect($host, $user, $pass, $port = 22);

    abstract public function getFileContent($path);
}
