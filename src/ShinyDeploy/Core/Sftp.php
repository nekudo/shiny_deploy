<?php
namespace ShinyDeploy\Core;

class Sftp
{
    /** @var string $errorMsg */
    private $errorMsg =  null;

    /** @var resource $sshConnection */
    private $sshConnection = null;

    /** @var resource $sftpConnection */
    private $sftpConnection = null;

    // TODO: Auto connect if server data is passed.
    public function __construct()
    {
    }

    // TODO: Destroy open connections.
    public function __destruct()
    {
    }

    /**
     * Opens ssh2 connection and sets sftp handle.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param int $port
     * @return bool True if connection could be established False if not.
     */
    public function connect($host, $user, $pass, $port = 22)
    {
        if (empty($host) || empty($user)) {
            return false;
        }
        $this->sshConnection = ssh2_connect($host, $port);
        if ($this->sshConnection === false) {
            $this->setError(2);
            return false;
        }
        if (ssh2_auth_password($this->sshConnection, $user, $pass) === false) {
            $this->setError(3);
            return false;
        }
        if (($this->sftpConnection = ssh2_sftp($this->sshConnection)) === false) {
            $this->setError(6);
            return false;
        }
        return true;
    }

    /**
     * Close ssh connection by un-setting connection handle.
     *
     * @return bool true if connection is closed false on error.
     */
    public function disconnect()
    {
        if ($this->sshConnection === null || $this->sshConnection === false) {
            $this->setError(4);
            return false;
        }
        unset($this->sshConnection);
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
    public function mkdir($path, $mode = 0755, $recursive = false)
    {
        if (ssh2_sftp_mkdir($this->sftpConnection, $path, $mode, $recursive) === false) {
            $this->setError(5);
            return false;
        }
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
    public function put($localFile, $remoteFile, $mode = 0644)
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $sftpStream = @fopen('ssh2.sftp://' . $this->sftpConnection . $remoteFile, 'w');
        if ($sftpStream === false) {
            $this->setError(7);
            return false;
        }
        $dataToSend = file_get_contents($localFile);
        if ($dataToSend === false) {
            $this->setError(7);
            return false;
        }
        if (fwrite($sftpStream, $dataToSend) === false) {
            $this->setError(7);
            return false;
        }
        fclose($sftpStream);
        return true;
    }

    /**
     * Fetches remote file.
     * If local file is provided content will be store to file.
     *
     * @param string $remoteFile
     * @return bool|string
     */
    public function get($remoteFile)
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $sftpStream = @fopen('ssh2.sftp://' . $this->sftpConnection . $remoteFile, 'r');
        if ($sftpStream === false) {
            $this->setError(7);
            return false;
        }
        $content = '';
        while (!feof($sftpStream)) {
            $content .= fread($sftpStream, 8192);
        }
        fclose($sftpStream);
        return $content;
    }

    public function download($remoteFile, $localFile)
    {
        // @todo implement...
    }

    /**
     * Deletes a file on remote server.
     *
     * @param string $file
     * @return bool
     */
    public function unlink($file)
    {
        if (!ssh2_sftp_unlink($this->sftpConnection, $file) === false) {
            $this->setError(8);
            return false;
        }
        return true;
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
        if (ssh2_sftp_rename($this->sftpConnection, $filenameFrom, $filenameTo) === false) {
            $this->setError(10);
            return false;
        }
        return true;
    }

    /**
     * List directory content.
     *
     * @param string $path Path to directory which should be listed.
     * @return array $filelist List of directory content.
     */
    public function listdir($path = '/')
    {
        $dir = 'ssh2.sftp://' . $this->sftpConnection . $path;
        $filelist = [];
        if (($handle = opendir($dir)) !== false) {
            while (false !== ($file = readdir($handle))) {
                if (substr($file, 0, 1) != ".") {
                    $filelist[] = $file;
                }
            }
            closedir($handle);
            return $filelist;
        } else {
            $this->setError(9);
            return false;
        }
    }

    /**
     * Sets an error message by passing an error code.
     *
     * @param int $errorCode Numeric value representing an error message.
     * @return bool True if massage was set falseCn error.
     */
    private function setError($errorCode)
    {
        switch($errorCode) {
            case 1:
                $this->errorMsg = 'Server data not complete.';
                return true;
                break;
            case 2:
                $this->errorMsg = 'Connection to Server could not be established.';
                return true;
                break;
            case 3:
                $this->errorMsg = 'Could not authenticate at server.';
                return true;
                break;
            case 4:
                $this->errorMsg = 'No active connection to close.';
                return true;
                break;
            case 5:
                $this->errorMsg = 'Could not create dir.';
                return true;
                break;
            case 6:
                $this->errorMsg = 'Could not initialize sftp subsystem.';
                return true;
                break;
            case 7:
                $this->errorMsg = 'Could not upload file to target server.';
                return true;
                break;
            case 8:
                $this->errorMsg = 'Could not delete remote file.';
                break;
            case 9:
                $this->errorMsg = 'Could not open remote directory.';
                break;
            case 10:
                $this->errorMsg = 'Could not rename file.';
                break;
            default:
                return false;
                break;
        }
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
