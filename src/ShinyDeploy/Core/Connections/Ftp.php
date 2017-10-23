<?php
namespace ShinyDeploy\Core\Connections;

class Ftp
{
    /** @var string $errorMsg */
    private $errorMsg =  null;

    /** @var resource $ftpConnection */
    private $ftpConnection = null;

    /** @var array $existingFolders */
    protected $existingFolders = [];

    // TODO: Auto connect if server data is passed.
    public function __construct()
    {
    }

    // TODO: Destroy open connections.
    public function __destruct()
    {
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
    public function connect($host, $user, $pass, $port = 22)
    {

    }

    /**
     * Closes ftp connection.
     *
     * @return bool true if connection is closed false on error.
     */
    public function disconnect()
    {

    }

    /**
     * Create a folder on destination server.
     *
     * @param string $path The path to create.
     * @param int $mode The chmod value the folder should have.
     * @param bool $recursive On true all parent folders are created too.
     * @return bool True on success false on error.
     */
    public function mkdir($path, $mode = 0755, $recursive = false)
    {

    }

    /**
     * Uploads a file to destination server using ftp.
     *
     * @param string $localFile Path to local file.
     * @param string $remoteFile Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function put($localFile, $remoteFile, $mode = 0644)
    {

    }

    /**
     * Uploads a file to destination server using ftp.
     *
     * @param string $content Content to put into remote file
     * @param string $remoteFile Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function putContent($content, $remoteFile, $mode = 0644)
    {

    }

    /**
     * Fetches remote file.
     * If local file is provided content will be stored to file.
     *
     * @param string $remoteFile
     * @return bool|string
     */
    public function get($remoteFile)
    {

    }

    public function download($remoteFile, $localFile)
    {

    }

    /**
     * Deletes a file on remote server.
     *
     * @param string $file
     * @return bool
     */
    public function unlink($file)
    {

    }

    /**
     * Renames a file on remote server.
     *
     * @param $filenameFrom
     * @param $filenameTo
     * @return bool
     */
    public function rename($filenameFrom, $filenameTo)
    {

    }

    /**
     * List directory content.
     *
     * @param string $path Path to directory which should be listed.
     * @return array $filelist List of directory content.
     */
    public function listdir($path = '/')
    {

    }



    /**
     * Sets an error message by passing an error code.
     *
     * @param int $errorCode Numeric value representing an error message.
     * @return bool True if massage was set falseCn error.
     */
    protected function setError($errorCode)
    {

    }

    /**
     * Return the current error message.
     *
     * @return string The error message.
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }
}
