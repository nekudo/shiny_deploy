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
        unset($this->ssh_connection);
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
     * @param string $local_file Path to local file.
     * @param string $remote_file Path to destination file.
     * @param int $mode Chmod destination file to this value.
     * @return bool True on success false on error.
     */
    public function put($local_file, $remote_file, $mode = 0644)
    {
        if (ssh2_scp_send($this->sshConnection, $local_file, $remote_file, $mode) === false) {
            $this->setError(7);
            return false;
        }
        return true;
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
                $this->$errorMsg = 'Server data not complete.';
                return true;
                break;
            case 2:
                $this->$errorMsg = 'Connection to Server could not be established.';
                return true;
                break;
            case 3:
                $this->$errorMsg = 'Could not authenticate at server.';
                return true;
                break;
            case 4:
                $this->$errorMsg = 'No active connection to close.';
                return true;
                break;
            case 5:
                $this->$errorMsg = 'Could not create dir.';
                return true;
                break;
            case 6:
                $this->$errorMsg = 'Could not initialize sftp subsystem.';
                return true;
                break;
            case 7:
                $this->$errorMsg = 'Could not upload file to target server.';
                return true;
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
        return $this->$errorMsg;
    }
}
