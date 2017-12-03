<?php
namespace ShinyDeploy\Core\Connections;

class Sftp extends Ssh
{
    /**
     * Uploads a file to destination server using sftp.
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

    public function download($remoteFile, $localFile)
    {
        // @todo implement...
    }
}
