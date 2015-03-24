<?php
namespace ShinyDeploy\Domain;

use ShinyDeploy\Core\Domain;
use ShinyDeploy\Core\Server\Server;
use ShinyDeploy\Core\Server\SftpServer;

class Deploy extends Domain
{
    /** @var array $supportedServerTypes */
    protected $supportedServerTypes = ['sftp'];

    /**
     * Creates server object.
     *
     * @param string $type
     * @return mixed
     */
    public function getServer($type)
    {
        if (!in_array($type, $this->supportedServerTypes)) {
            throw new \RuntimeException('Unknown server-type.');
        }
        switch ($type) {
            case 'sftp':
                return new SftpServer;
                break;
        }
        return false;
    }

    /**
     * Checks if connection to server is possible.
     *
     * @param Server $server
     * @param array $credentials
     * @return bool
     */
    public function checkConnectivity(Server $server, array $credentials)
    {
        $connectionResult = $server->connect(
            $credentials['host'],
            $credentials['user'],
            $credentials['pass'],
            $credentials['port']
        );
        return $connectionResult;
    }
}
