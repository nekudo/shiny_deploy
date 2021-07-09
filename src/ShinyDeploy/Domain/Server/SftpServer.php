<?php
namespace ShinyDeploy\Domain\Server;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Core\Connections\Sftp;
use ShinyDeploy\Core\Connections\Ssh;

class SftpServer extends SshServer
{
    /** @var Ssh $connection */
    protected Ssh $connection;

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
        $this->connection = new Sftp();
    }

    /**
     * Returns server type.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'sftp';
    }
}
