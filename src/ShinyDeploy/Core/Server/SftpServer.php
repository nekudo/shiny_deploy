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
}
