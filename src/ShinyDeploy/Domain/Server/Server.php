<?php
namespace ShinyDeploy\Domain\Server;

use ShinyDeploy\Core\Domain;

abstract class Server extends Domain
{
    abstract public function getType();

    abstract public function connect($host, $user, $pass, $port = 22);

    abstract public function getFileContent($path);

    abstract public function upload($localFile, $remoteFile, $mode = 0644);

    abstract public function putContent($content, $remoteFile, $mode = 0644);

    abstract public function delete($remoteFile);

    abstract public function listDir($remotePath);

    abstract public function checkConnectivity();

    /**
     * Returns servers root path.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getRootPath()
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Server data not found. Initialization missing?');
        }
        $remotePath = trim($this->data['root_path']);
        return $remotePath;
    }
}
