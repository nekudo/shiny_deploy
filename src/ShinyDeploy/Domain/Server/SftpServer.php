<?php
namespace ShinyDeploy\Domain\Server;

use ShinyDeploy\Core\Connections\Sftp;

class SftpServer extends SshServer
{
    /** @var Sftp $connection */
    protected $connection;

    public function __construct()
    {
        parent::__construct();
        $this->connection = new Sftp;
    }
}
