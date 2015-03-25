<?php
namespace ShinyDeploy\Core\Server;

use ShinyDeploy\Core\Sftp;

class SftpServer extends Server
{
    /** @var Sftp $connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = new Sftp;
    }

    /**
     * Connects to remote server
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param int $port
     * @return bool
     */
    public function connect($host, $user, $pass, $port = 22)
    {
        return $this->connection->connect($host, $user, $pass, $port);
    }

    /**
     * Fetches content of remote file.
     *
     * @param string $path
     * @return bool|string
     */
    public function getFileContent($path)
    {
        if (empty($path)) {
            throw new \RuntimeException('Path can not be empty.');
        }
        $fileContent = $this->connection->get($path);
        if ($fileContent === false) {
            return false;
        }
        return $fileContent;
    }
}
