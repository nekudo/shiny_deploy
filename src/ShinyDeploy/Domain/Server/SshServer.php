<?php
namespace ShinyDeploy\Domain\Server;

use Apix\Log\Logger;
use Noodlehaus\Config;
use RuntimeException;
use ShinyDeploy\Core\Connections\Ssh;
use ShinyDeploy\Exceptions\ConnectionException;

class SshServer extends Server
{
    /** @var Ssh $connection */
    protected Ssh $connection;

    /**
     * @var string $connectionHash
     */
    protected string $connectionHash = '';

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
        $this->connection = new Ssh();
    }

    /**
     * Returns server type.
     *
     * @return string
     */
    public function getType(): string
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
    public function connect(string $host, string $user, string $pass, int $port = 22): bool
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
     * @return bool
     */
    public function disconnect(): bool
    {
        $this->connection->disconnect();
        $this->connectionHash = '';
        return true;
    }

    /**
     * Fetches content of remote file.
     *
     * @param string $path
     * @return string
     */
    public function getFileContent(string $path): string
    {
        if (empty($path)) {
            throw new RuntimeException('Path can not be empty.');
        }

        return $this->connection->get($path);
    }

    /**
     * Uploads a file to remote server.
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param int $mode
     * @return bool
     */
    public function upload(string $localFile, string $remoteFile, int $mode = 0644): bool
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
    public function putContent(string $content, string $remoteFile, int $mode = 0644): bool
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
    public function delete(string $remoteFile): bool
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
     * @throws ConnectionException
     */
    public function listDir(string $remotePath): array
    {
        if (empty($remotePath)) {
            throw new RuntimeException('Required parameter missing.');
        }
        return $this->connection->listdir($remotePath);
    }

    /**
     * Executes ssh command on server.
     *
     * @param string $command
     * @return string
     */
    public function executeCommand(string $command): string
    {
        if (empty($command)) {
            throw new RuntimeException('Required parameter missing.');
        }
        return $this->connection->exec($command);
    }

    /**
     * Checks if connection to server is possible.
     *
     * @return bool
     */
    public function checkConnectivity(): bool
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
