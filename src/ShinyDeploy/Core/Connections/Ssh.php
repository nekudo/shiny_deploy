<?php
namespace ShinyDeploy\Core\Connections;

use ShinyDeploy\Exceptions\ConnectionException;

class Ssh
{
    /** @var string $errorMsg */
    private $errorMsg =  null;

    /** @var resource $sshConnection */
    private $sshConnection = null;

    /** @var resource $sftpConnection */
    protected $sftpConnection = null;

    /** @var array $existingFolders */
    protected $existingFolders = [];

    /**
     * Opens ssh2 connection and sets sftp handle.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param int $port
     * @return bool True if connection could be established False if not.
     */
    public function connect(string $host, string $user, string $pass, int $port = 22) : bool
    {
        if (empty($host) || empty($user)) {
            return false;
        }
        $this->sshConnection = @ssh2_connect($host, $port);
        if ($this->sshConnection === false) {
            $this->setError(2);
            return false;
        }
        if (@ssh2_auth_password($this->sshConnection, $user, $pass) === false) {
            $this->setError(3);
            return false;
        }
        if (($this->sftpConnection = @ssh2_sftp($this->sshConnection)) === false) {
            $this->setError(6);
            return false;
        }
        $this->existingFolders = [];
        return true;
    }

    /**
     * Close ssh connection by un-setting connection handle.
     *
     * @return bool true if connection is closed false on error.
     */
    public function disconnect() : bool
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
    public function mkdir(string $path, int $mode = 0755, bool $recursive = false) : bool
    {
        if (ssh2_sftp_mkdir($this->sftpConnection, $path, $mode, $recursive) === false) {
            $this->setError(5);
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
    public function put(string $localFile, string $remoteFile, int $mode = 0644) : bool
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $remoteDir = dirname($remoteFile);
        if (!isset($this->existingFolders[$remoteDir])) {
            $this->mkdir($remoteDir, 0755, true);
        }
        if (file_exists($localFile) === false) {
            $this->setError(7);
            return false;
        }
        if (ssh2_scp_send($this->sshConnection, $localFile, $remoteFile, $mode) === false) {
            $this->setError(7);
            return false;
        }

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
    public function putContent(string $content, string $remoteFile, int $mode = 0644) : bool
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $remoteDir = dirname($remoteFile);
        if (!isset($this->existingFolders[$remoteDir])) {
            $this->mkdir($remoteDir, 0755, true);
        }
        $sftpStream = @fopen('ssh2.sftp://' . (int)$this->sftpConnection . $remoteFile, 'w');
        if ($sftpStream === false) {
            $this->setError(7);
            return false;
        }
        if (fwrite($sftpStream, $content) === false) {
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
     * @throws ConnectionException
     * @return string
     */
    public function get(string $remoteFile) : string
    {
        $remoteFile = (substr($remoteFile, 0, 1) != '/') ? '/' . $remoteFile : $remoteFile;
        $sftpStream = @fopen('ssh2.sftp://' . (int)$this->sftpConnection . $remoteFile, 'r');
        if ($sftpStream === false) {
            throw new ConnectionException('Could not open sftp connection.');
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
    public function unlink(string $file) : bool
    {
        if (ssh2_sftp_unlink($this->sftpConnection, $file) === false) {
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
    public function rename(string $filenameFrom, string $filenameTo) : bool
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
     * @throws ConnectionException
     * @return array $filelist List of directory content.
     */
    public function listdir(string $path = '/') : array
    {
        $dir = 'ssh2.sftp://' . (int)$this->sftpConnection . $path;
        $filelist = [];
        if (($handle = @opendir($dir)) !== false) {
            while (false !== ($file = readdir($handle))) {
                if (substr($file, 0, 1) != ".") {
                    $filelist[] = $file;
                }
            }
            closedir($handle);
            return $filelist;
        } else {
            $this->setError(9);
            throw new ConnectionException('Could not open target directory.');
        }
    }

    /**
     * Executes a custom ssh command.
     * HINT: Returns all possible responses from stdout and stderr as some applications (e.g. composer)
     * use stderr for outputs.
     *
     * @param string $cmd
     * @param string $pty
     * @param array $env
     * @param int $width
     * @param int $height
     * @param int $width_height_type
     * @return string
     */
    public function exec(
        string $cmd,
        string $pty = null,
        array $env = [],
        int $width = 80,
        int $height = 25,
        int $width_height_type = SSH2_TERM_UNIT_CHARS
    ) : string {
        $stdout = ssh2_exec($this->sshConnection, $cmd, $pty, $env, $width, $height, $width_height_type);
        $stderr = ssh2_fetch_stream($stdout, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);
        stream_set_blocking($stdout, true);
        $error = stream_get_contents($stderr);
        $output = stream_get_contents($stdout);
        return $output . $error;
    }

    /**
     * Sets an error message by passing an error code.
     *
     * @param int $errorCode Numeric value representing an error message.
     * @return bool True if massage was set falseCn error.
     */
    protected function setError(int $errorCode) : bool
    {
        switch ($errorCode) {
            case 1:
                $this->errorMsg = 'Server data not complete.';
                return true;
            case 2:
                $this->errorMsg = 'Connection to Server could not be established.';
                return true;
            case 3:
                $this->errorMsg = 'Could not authenticate at server.';
                return true;
            case 4:
                $this->errorMsg = 'No active connection to close.';
                return true;
            case 5:
                $this->errorMsg = 'Could not create dir.';
                return true;
            case 6:
                $this->errorMsg = 'Could not initialize sftp subsystem.';
                return true;
            case 7:
                $this->errorMsg = 'Could not upload file to target server.';
                return true;
            case 8:
                $this->errorMsg = 'Could not delete remote file.';
                return true;
            case 9:
                $this->errorMsg = 'Could not open remote directory.';
                return true;
            case 10:
                $this->errorMsg = 'Could not rename file.';
                return true;
            case 11:
                $this->errorMsg = 'Could not execute ssh command.';
                return true;
            default:
                return false;
        }
    }

    /**
     * Return the current error message.
     *
     * @return string The error message.
     */
    public function getErrorMsg() : string
    {
        return $this->errorMsg;
    }
}
