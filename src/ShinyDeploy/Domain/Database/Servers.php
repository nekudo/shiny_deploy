<?php
namespace ShinyDeploy\Domain\Database;

use InvalidArgumentException;
use RuntimeException;
use ShinyDeploy\Domain\Server\FtpServer;
use ShinyDeploy\Domain\Server\Server;
use ShinyDeploy\Domain\Server\SftpServer;
use ShinyDeploy\Domain\Server\SshServer;
use ShinyDeploy\Exceptions\DatabaseException;
use ShinyDeploy\Traits\CryptableDomain;

class Servers extends DatabaseDomain
{
    use CryptableDomain;

    /** @var array $rules Validation rules */
    protected $rules = [
        'required' => [
            ['name'],
            ['type'],
            ['hostname'],
            ['port'],
            ['root_path']
        ],
        'integer' => [
            ['port']
        ],
        'in' => [
            ['type', ['sftp', 'ssh', 'ftp']]
        ],
        'hostname' => [
            ['hostname']
        ],
    ];

    /** @var array $encryptedFields Fields that are encrypted in database. */
    protected $encryptedFields = [
        'hostname',
        'port',
        'username',
        'password',
        'root_path',
    ];

    /**
     * Get validation rules for insert queries.
     *
     * @return array
     */
    public function getCreateRules() : array
    {
        return $this->rules;
    }

    /**
     * Get validation rules for update queries.
     *
     * @return array
     */
    public function getUpdateRules() : array
    {
        $rules = $this->rules;
        $rules['required'][] = ['id'];
        return $this->rules;
    }

    /**
     * Creates and returns a server object.
     *
     * @param int $serverId
     * @return Server
     * @throws RuntimeException
     * @throws DatabaseException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     */
    public function getServer(int $serverId) : Server
    {
        $data = $this->getServerData($serverId);
        if (empty($data)) {
            throw new RuntimeException('Server not found in database.');
        }
        switch ($data['type']) {
            case 'ssh':
                $server = new SshServer($this->config, $this->logger);
                break;
            case 'sftp':
                $server = new SftpServer($this->config, $this->logger);
                break;
            case 'ftp':
                $server = new FtpServer($this->config, $this->logger);
                break;
            default:
                throw new RuntimeException('Invalid server type.');
        }
        $server->init($data);
        return $server;
    }

    /**
     * Fetches list of servers from database.
     *
     * @throws DatabaseException
     * @throws RuntimeException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @return array
     */
    public function getServers() : array
    {
        $rows = $this->db->prepare("SELECT * FROM servers ORDER BY `name`")->getResult(false);
        if (empty($rows)) {
            return $rows;
        }
        foreach ($rows as $i => $row) {
            $decryptedRow = $this->decryptData($row, $this->encryptedFields);
            if ($decryptedRow === false) {
                throw new RuntimeException('Date decryption failed.');
            }
            $rows[$i] = $decryptedRow;
        }
        return $rows;
    }

    /**
     * Stores new server in database.
     *
     * @param array $serverData
     * @return bool
     * @throws DatabaseException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     */
    public function addServer(array $serverData) : bool
    {
        $serverData = $this->encryptData($serverData, $this->encryptedFields);
        if ($serverData === false) {
            throw new RuntimeException('Data encryption failed.');
        }

        return $this->db->prepare(
            "INSERT INTO servers
              (`name`, `type`, `hostname`, `port`, `username`, `password`, `root_path`)
              VALUES
                (%s, %s, %s, %s, %s, %s, %s)",
            $serverData['name'],
            $serverData['type'],
            $serverData['hostname'],
            $serverData['port'],
            $serverData['username'],
            $serverData['password'],
            $serverData['root_path']
        )->execute();
    }

    /**
     * Updates server.
     *
     * @param array $serverData
     * @return bool
     * @throws DatabaseException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     */
    public function updateServer(array $serverData) : bool
    {
        if (!isset($serverData['id'])) {
            return false;
        }

        $serverData = $this->encryptData($serverData, $this->encryptedFields);
        if ($serverData === false) {
            throw new RuntimeException('Data encryption failed.');
        }

        return $this->db->prepare(
            "UPDATE servers
            SET `name` = %s,
              `type` = %s,
              `hostname` = %s,
              `port` = %s,
              `username` = %s,
              `password` = %s,
              `root_path` = %s
            WHERE id = %i",
            $serverData['name'],
            $serverData['type'],
            $serverData['hostname'],
            $serverData['port'],
            $serverData['username'],
            $serverData['password'],
            $serverData['root_path'],
            $serverData['id']
        )->execute();
    }

    /**
     * Deletes a server.
     *
     * @param int $serverId
     * @return bool
     * @throws DatabaseException
     */
    public function deleteServer(int $serverId) : bool
    {
        $serverId = (int)$serverId;
        if ($serverId === 0) {
            return false;
        }
        return $this->db->prepare("DELETE FROM servers WHERE id = %i LIMIT 1", $serverId)->execute();
    }

    /**
     * Fetches server data.
     *
     * @param int $serverId
     * @return array
     * @throws DatabaseException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     */
    public function getServerData(int $serverId) : array
    {
        $serverId = (int)$serverId;
        if ($serverId === 0) {
            return [];
        }
        $serverData = $this->db->prepare("SELECT * FROM servers WHERE id = %i", $serverId)->getResult(true);
        if (empty($serverData)) {
            return [];
        }

        $serverData = $this->decryptData($serverData, $this->encryptedFields);
        if ($serverData === false) {
            throw new RuntimeException('Data decryption failed.');
        }
        return $serverData;
    }

    /**
     * Checks whether any relations to given server exist.
     *
     * @param int $serverId
     * @return bool
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function serverInUse(int $serverId) : bool
    {
        $serverId = (int)$serverId;
        if (empty($serverId)) {
            throw  new InvalidArgumentException('serverId can not be empty.');
        }
        $cnt = $this->db->prepare("SELECT COUNT(id) FROM deployments WHERE `server_id` = %i", $serverId)->getValue();
        return ($cnt > 0);
    }
}
