<?php
namespace ShinyDeploy\Core\Server;

abstract class Server
{
    abstract public function connect($host, $user, $pass, $port = 22);

    abstract public function getFileContent($path);

    abstract public function upload($localFile, $remoteFile, $mode = 0644);

    abstract public function putContent($content, $remoteFile, $mode = 0644);

    abstract public function delete($remoteFile);

    abstract public function listDir($remotePath);
}
