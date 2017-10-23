<?php
namespace ShinyDeploy\Domain\Server;

use Apix\Log\Logger;
use Noodlehaus\Config;
use RuntimeException;
use ShinyDeploy\Core\Connections\Ftp;

class FtpServer extends Server
{
    /** @var Ftp $connection */
    protected $connection;

    protected $connectionHash = '';

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
        $this->connection = new Ftp;
    }

    /**
     * Returns server type.
     *
     * @return string
     */
    public function getType()
    {
        return 'ssh';
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
        $hash = md5($host . $user . $pass . $port);
        if ($hash === $this->connectionHash) {
            return true;
        }
        $connectionResult =  $this->connection->connect($host, $user, $pass, $port);
        if ($connectionResult === false) {
            return false;
        }
        $this->connectionHash = $hash;
        return true;
    }

    /**
     * Closes connection to remote server.
     *
     * @return boolean
     */
    public function disconnect()
    {
        $this->connection->disconnect();
        $this->connectionHash = '';
        return true;
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
            throw new RuntimeException('Path can not be empty.');
        }
        $fileContent = $this->connection->get($path);
        if ($fileContent === false) {
            return false;
        }
        return $fileContent;
    }

    /**
     * Uploads a file to remote server.
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param int $mode
     * @return bool
     */
    public function upload($localFile, $remoteFile, $mode = 0644)
    {
        if (empty($localFile) || empty($remoteFile)) {
            throw new RuntimeException('Required parameter missing.');
        }
        if (!file_exists($localFile)) {
            return false;
        }
        return $this->connection->put($localFile, $remoteFile, $mode);
    }

    /**
     * Put content into remote file.
     *
     * @param string $content
     * @param string $remoteFile
     * @param int $mode
     * @return bool
     */
    public function putContent($content, $remoteFile, $mode = 0644)
    {
        if (empty($remoteFile)) {
            throw new RuntimeException('Required parameter missing.');
        }
        return $this->connection->putContent($content, $remoteFile, $mode);
    }

    /**
     * Removes a file on remote server.
     *
     * @param string $remoteFile
     * @return bool
     */
    public function delete($remoteFile)
    {
        if (empty($remoteFile)) {
            throw new RuntimeException('Required parameter missing.');
        }
        return $this->connection->unlink($remoteFile);
    }

    /**
     * Lists contents of remote directory.
     *
     * @param string $remotePath
     * @return array
     */
    public function listDir($remotePath)
    {
        if (empty($remotePath)) {
            throw new RuntimeException('Required parameter missing.');
        }
        return $this->connection->listdir($remotePath);
    }

    /**
     * Checks if connection to server is possible.
     *
     * @return bool
     */
    public function checkConnectivity()
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Server data empty. Initialization missing?');
        }
        return $this->connect(
            $this->data['hostname'],
            $this->data['username'],
            $this->data['password'],
            $this->data['port']
        );
    }
}
