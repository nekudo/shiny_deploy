<?php
namespace ShinyDeploy\Domain;

class Servers extends DatabaseDomain
{
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
            ['type', ['sftp', 'ssh']]
        ],
        'hostname' => [
            ['hostname', true]
        ],
    ];

    /**
     * Get validation rules for insert queries.
     *
     * @return array
     */
    public function getCreateRules()
    {
        return $this->rules;
    }

    /**
     * Get validation rules for update queries.
     *
     * @return array
     */
    public function getUpdateRules()
    {
        $rules = $this->rules;
        $rules['required'][] = ['id'];
        return $this->rules;
    }
    /**
     * Fetches list of servers from database.
     *
     * @return array|bool
     */
    public function getServers()
    {
        $rows = $this->db->prepare("SELECT * FROM servers ORDER BY `name`")->getResult(false);
        return $rows;
    }

    /**
     * Stores new server in database.
     *
     * @param array $serverData
     * @return bool
     */
    public function addServer(array $serverData)
    {
        return $this->db->prepare(
            "INSERT INTO servers
              (`name`, `type`, `hostname`, `port`, `username`, `password`, `root_path`)
              VALUES
                (%s, %s, %s, %d, %s, %s, %s)",
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
     */
    public function updateServer(array $serverData)
    {
        if (!isset($serverData['id'])) {
            return false;
        }
        return $this->db->prepare(
            "UPDATE servers
            SET `name` = %s,
              `type` = %s,
              `hostname` = %s,
              `port` = %d,
              `username` = %s,
              `password` = %s,
              `root_path` = %s
            WHERE id = %d",
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
     * Fetches server data.
     *
     * @param int $serverId
     * @return array
     */
    public function getServerData($serverId)
    {
        $serverId = (int)$serverId;
        if ($serverId === 0) {
            return [];
        }
        $serverData = $this->db->prepare("SELECT * FROM servers WHERE id = %d", $serverId)->getResult(true);
        if (empty($serverData)) {
            return [];
        }
        return $serverData;
    }
}
