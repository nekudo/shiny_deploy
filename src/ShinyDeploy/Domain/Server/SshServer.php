<?php
namespace ShinyDeploy\Domain\Server;

use ShinyDeploy\Core\Connections\Ssh;

class SshServer extends Server
{
    /** @var Ssh $connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = new Ssh;
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
            throw new \RuntimeException('Required parameter missing.');
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
            throw new \RuntimeException('Required parameter missing.');
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
            throw new \RuntimeException('Required parameter missing.');
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
            throw new \RuntimeException('Required parameter missing.');
        }
        return $this->connection->listdir($remotePath);
    }

    /**
     * Executes ssh command on server.
     *
     * @param string $command
     * @return bool|string
     */
    public function executeCommand($command)
    {
        if (empty($command)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        return $this->connection->exec($command);
    }
}
