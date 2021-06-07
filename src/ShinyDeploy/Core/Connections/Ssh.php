<?php
namespace ShinyDeploy\Core\Connections;

use phpseclib3\Net\SFTP;
use ShinyDeploy\Exceptions\ConnectionException;

class Ssh
{
    /** @var SFTP $connection  */
    private SFTP $connection;

    /** @var array $existingFolders */
    protected array $existingFolders = [];

    /**
     * Opens ssh2 connection and sets sftp handle.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param int $port
     * @return bool True if connection could be established False if not.
     */
    public function connect(string $host, string $user, string $pass, int $port = 22): bool
    {
        if (empty($host) || empty($user)) {
            return false;
        }

        $this->existingFolders = [];
        $this->connection = new SFTP($host, $port);

        return $this->connection->login($user, $pass);
    }

    /**
     * Close ssh connection by un-setting connection handle.
     *
     * @return bool true if connection is closed false on error.
     */
    public function disconnect(): bool
    {
        if (!empty($this->connection)) {
            $this->connection->disconnect();
        }
        unset($this->connection);
        return true;
    }

    /**
     * Create a folder on destination server.
     *
     * @param string $path The path to create.
     * @param int $mode The chmod value the folder should have.
     * @param bool $recursive On true all parent folders are created too.
     * @return bool True on success false on error.
     */
    public function mkdir(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        if ($this->connection->mkdir($path, $mode, $recursive) === false) {
            return false;
        }
        $this->existingFolders[$path] = true;
        return true;
    }

    /**
     * Uploads a file to destination server using scp.
     *
     * @param string $localFile Path to local file.
     * @param string $remoteFile Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function put(string $localFile, string $remoteFile, int $mode = 0644): bool
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $remoteDir = dirname($remoteFile);
        if (!isset($this->existingFolders[$remoteDir])) {
            $this->mkdir($remoteDir, 0755, true);
        }
        if (file_exists($localFile) === false) {
            return false;
        }
        if ($this->connection->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE) === false) {
            return false;
        }
        $this->connection->chmod($mode, $remoteFile);
        return true;
    }

    /**
     * Uploads a file to destination server using scp.
     *
     * @param string $content Content to put into remote file
     * @param string $remoteFile Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function putContent(string $content, string $remoteFile, int $mode = 0644): bool
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $remoteDir = dirname($remoteFile);
        if (!isset($this->existingFolders[$remoteDir])) {
            $this->mkdir($remoteDir, 0755, true);
        }

        if ($this->connection->put($remoteFile, $content) === false) {
            return false;
        }
        $this->connection->chmod($mode, $remoteFile);
        return true;
    }

    /**
     * Fetches remote file.
     *
     * @param string $remoteFile
     * @return string
     */
    public function get(string $remoteFile): string
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $content = $this->connection->get($remoteFile);
        if ($content === false) {
            return '';
        }
        return $content;
    }

    /**
     * Deletes a file on remote server.
     *
     * @param string $file
     * @return bool
     */
    public function unlink(string $file): bool
    {
        return $this->connection->delete($file);
    }

    /**
     * Renames a file on remote server.
     *
     * @param $filenameFrom
     * @param $filenameTo
     * @return bool
     */
    public function rename(string $filenameFrom, string $filenameTo): bool
    {
        return $this->connection->rename($filenameFrom, $filenameTo);
    }

    /**
     * List directory content.
     *
     * @param string $path Path to directory which should be listed.
     * @throws ConnectionException
     * @return array $filelist List of directory content.
     */
    public function listdir(string $path = '/'): array
    {
        $filelist = $this->connection->nlist($path);
        if ($filelist === false) {
            throw new ConnectionException('Could not open target directory.');
        }

        // remove dot-directories:
        $filelist = array_diff($filelist, ['.', '..']);

        return $filelist;
    }

    /**
     * Executes a custom ssh command.
     * HINT: Returns all possible responses from stdout and stderr as some applications (e.g. composer)
     * use stderr for outputs.
     *
     * @param string $cmd
     * @return string
     */
    public function exec(string $cmd): string
    {
        $response = $this->connection->exec($cmd);
        if ($response === false) {
            return '';
        }
        return $response;
    }
}
