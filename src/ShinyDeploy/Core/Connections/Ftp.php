<?php
namespace ShinyDeploy\Core\Connections;

use FtpClient\FtpClient;
use FtpClient\FtpException;
use ShinyDeploy\Exceptions\ConnectionException;

class Ftp
{
    /** @var string $errorMsg */
    private string $errorMsg =  '';

    /** @var FtpClient $ftpClient */
    private FtpClient $ftpClient;

    /** @var array $existingFolders */
    protected array $existingFolders = [];

    public function __construct()
    {
        $this->ftpClient = new FtpClient();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Opens ftp connection.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param int $port
     * @return bool True if connection could be established False if not.
     */
    public function connect(string $host, string $user, string $pass, int $port = 22): bool
    {
        try {
            $this->ftpClient->connect($host, false, $port, 30);
            $this->ftpClient->login($user, $pass);
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Closes ftp connection.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->ftpClient->close();
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
        try {
            if ($this->ftpClient->isDir($path) === false) {
                $this->ftpClient->mkdir($path, $recursive);
                $this->ftpClient->chmod($mode, $path);
            }
            $this->existingFolders[$path] = true;
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Uploads a file to destination server using ftp.
     *
     * @param string $localFile Path to local file.
     * @param string $remoteFile Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function put(string $localFile, string $remoteFile, int $mode = 0644): bool
    {
        try {
            $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
            $remoteDir = dirname($remoteFile);
            if (!isset($this->existingFolders[$remoteDir])) {
                $this->mkdir($remoteDir, 0755, true);
            }
            $this->ftpClient->put($remoteFile, $localFile, FTP_BINARY);
            $this->ftpClient->chmod($mode, $remoteFile);
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Uploads a file to destination server using ftp.
     *
     * @param string $content Content to put into remote file
     * @param string $remoteFile Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function putContent(string $content, string $remoteFile, int $mode = 0644): bool
    {
        try {
            $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
            $remoteDir = dirname($remoteFile);
            if (!isset($this->existingFolders[$remoteDir])) {
                $this->mkdir($remoteDir, 0755, true);
            }
            $this->ftpClient->putFromString($remoteFile, $content);
            $this->ftpClient->chmod($mode, $remoteFile);
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Fetches remote file.
     *
     * @param string $remoteFile
     * @throws ConnectionException
     * @return string
     */
    public function get(string $remoteFile): string
    {
        try {
            $tempHandle = fopen('php://temp', 'r+');
            $this->ftpClient->fget($tempHandle, $remoteFile, FTP_BINARY, 0);
            rewind($tempHandle);
            $content = stream_get_contents($tempHandle);
            fclose($tempHandle);
            return $content;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            throw new ConnectionException('Could not fetch file.');
        }
    }

    /**
     * Downloads a remote file to local server.
     *
     * @param string $remoteFile
     * @param string $localFile
     * @return bool
     */
    public function download(string $remoteFile, string $localFile): bool
    {
        try {
            $this->ftpClient->get($localFile, $remoteFile, FTP_BINARY, 0);
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a file on remote server.
     *
     * @param string $file
     * @return bool
     */
    public function unlink(string $file): bool
    {
        try {
            $this->ftpClient->delete($file);
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Renames a file on remote server.
     *
     * @param string $filenameFrom
     * @param string $filenameTo
     * @return bool
     */
    public function rename(string $filenameFrom, string $filenameTo): bool
    {
        try {
            $this->ftpClient->rename($filenameFrom, $filenameTo);
            return true;
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * List directory content.
     *
     * @param string $path Path to directory which should be listed.
     * @return array List of directory content.
     */
    public function listdir(string $path = '/'): array
    {
        try {
            return $this->ftpClient->nlist($path);
        } catch (FtpException $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }

    /**
     * Sets an error message.
     *
     * @param string $errorMessage The last error message.
     * @return void
     */
    protected function setError(string $errorMessage): void
    {
        $this->errorMsg = $errorMessage;
    }

    /**
     * Return the current error message.
     *
     * @return string The error message.
     */
    public function getErrorMsg(): string
    {
        return $this->errorMsg;
    }
}
