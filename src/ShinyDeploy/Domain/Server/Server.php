<?php
namespace ShinyDeploy\Domain\Server;

use ShinyDeploy\Core\Domain;

abstract class Server extends Domain
{
    abstract public function getType() : string;

    abstract public function connect(string $host, string $user, string $pass, int $port = 22) : bool;

    abstract public function getFileContent(string $path) : string;

    abstract public function upload(string $localFile, string $remoteFile, int $mode = 0644) : bool;

    abstract public function putContent(string $content, string $remoteFile, int $mode = 0644) : bool;

    abstract public function delete(string $remoteFile) : bool;

    abstract public function listDir(string $remotePath) : array;

    abstract public function checkConnectivity() : bool;

    /**
     * Connects to remote server.
     *
     * @param array $data
     */
    public function init(array $data) : void
    {
        parent::init($data);

        $connected = $this->connect(
            $this->data['hostname'],
            $this->data['username'],
            $this->data['password'],
            $this->data['port']
        );

        if ($connected === false) {
            $this->logger->warning('Could not connect to remote server.');
        }
    }

    /**
     * Returns servers root path.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getRootPath() : string
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Server data not found. Initialization missing?');
        }
        $remotePath = trim($this->data['root_path']);
        return $remotePath;
    }
}
