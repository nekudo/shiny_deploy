<?php
namespace ShinyDeploy\Domain\Database;

use RuntimeException;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Exceptions\DatabaseException;
use ShinyDeploy\Traits\CryptableDomain;

class Repositories extends DatabaseDomain
{
    use CryptableDomain;

    /** @var array $rules Validation rules */
    protected $rules = [
        'required' => [
            ['name'],
            ['type'],
            ['url'],
        ],
        'in' => [
            ['type', ['git']]
        ],
        'url' => [
            ['url']
        ],
    ];

    /** @var array $encryptedFields Fields that are encrypted in database. */
    protected $encryptedFields = [
        'url',
        'username',
        'password',
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
     * Creates and returns a repository object.
     *
     * @param int $repositoryId
     * @return Repository
     * @throws DatabaseException
     * @throws RuntimeException
     */
    public function getRepository(int $repositoryId) : Repository
    {
        $data = $this->getRepositoryData($repositoryId);
        if (empty($data)) {
            throw new RuntimeException('Repository not found in database.');
        }
        $repository = new Repository($this->config, $this->logger);
        $repository->init($data);
        return $repository;
    }


    /**
     * Fetches list of repositories from database.
     *
     * @throws DatabaseException
     * @return array
     */
    public function getRepositories() : array
    {
        $rows = $this->db->prepare("SELECT * FROM repositories ORDER BY `name`")->getResult(false);
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
     * Stores new repository in database.
     *
     * @param array $repositoryData
     * @throws DatabaseException
     * @return int
     */
    public function addRepository(array $repositoryData) : int
    {
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        $repositoryData = $this->encryptData($repositoryData, $this->encryptedFields);
        if ($repositoryData === false) {
            throw new RuntimeException('Data encryption failed.');
        }
        $this->db->prepare(
            "INSERT INTO repositories
              (`name`, `type`, `url`, `username`, `password`)
              VALUES
                (%s, %s, %s, %s, %s)",
            $repositoryData['name'],
            $repositoryData['type'],
            $repositoryData['url'],
            $repositoryData['username'],
            $repositoryData['password']
        )->execute();
        return $this->db->getInsertId();
    }

    /**
     * Updates repository.
     *
     * @param array $repositoryData
     * @throws DatabaseException
     * @return bool
     */
    public function updateRepository(array $repositoryData) : bool
    {
        if (!isset($repositoryData['id'])) {
            return false;
        }
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        $repositoryData = $this->encryptData($repositoryData, $this->encryptedFields);
        if ($repositoryData === false) {
            throw new RuntimeException('Data encryption failed.');
        }
        return $this->db->prepare(
            "UPDATE repositories
            SET `name` = %s,
              `type` = %s,
              `url` = %s,
              `username` = %s,
              `password` = %s
            WHERE id = %d",
            $repositoryData['name'],
            $repositoryData['type'],
            $repositoryData['url'],
            $repositoryData['username'],
            $repositoryData['password'],
            $repositoryData['id']
        )->execute();
    }

    /**
     * Deletes a repository.
     *
     * @param int $repositoryId
     * @throws DatabaseException
     * @return bool
     */
    public function deleteRepository(int $repositoryId) : bool
    {
        $repositoryId = (int)$repositoryId;
        if ($repositoryId === 0) {
            return false;
        }
        return $this->db->prepare("DELETE FROM repositories WHERE id = %d LIMIT 1", $repositoryId)->execute();
    }

    /**
     * Fetches repository data.
     *
     * @param int $repositoryId
     * @throws DatabaseException
     * @return array
     */
    public function getRepositoryData(int $repositoryId) : array
    {
        $repositoryId = (int)$repositoryId;
        if ($repositoryId === 0) {
            return [];
        }
        $repositoryData = $this->db
            ->prepare("SELECT * FROM repositories WHERE id = %d", $repositoryId)
            ->getResult(true);
        if (empty($repositoryData)) {
            return [];
        }
        $repositoryData = $this->decryptData($repositoryData, $this->encryptedFields);
        if ($repositoryData === false) {
            throw new RuntimeException('Data decryption failed.');
        }
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        return $repositoryData;
    }

    /**
     * Checks if relations to given repository id exist in database.
     *
     * @param int $repositoryId
     * @return bool
     * @throws DatabaseException
     */
    public function repositoryInUse(int $repositoryId) : bool
    {
        $repositoryId = (int)$repositoryId;
        if (empty($repositoryId)) {
            throw new RuntimeException('repositoryId can not be empty.');
        }
        $cnt = $this->db
            ->prepare("SELECT COUNT(id) FROM deployments WHERE `repository_id` = %d", $repositoryId)
            ->getValue();
        return ($cnt > 0);
    }
}
