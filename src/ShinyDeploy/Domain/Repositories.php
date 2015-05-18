<?php
namespace ShinyDeploy\Domain;

class Repositories extends DatabaseDomain
{
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
     * Fetches list of repositories from database.
     *
     * @return array|bool
     */
    public function getRepositories()
    {
        $rows = $this->db->prepare("SELECT * FROM repositories ORDER BY `name`")->getResult(false);
        return $rows;
    }

    /**
     * Stores new repository in database.
     *
     * @param array $repositoryData
     * @return bool|int
     */
    public function addRepository(array $repositoryData)
    {
        $result = $this->db->prepare(
            "INSERT INTO repositories
              (`name`, `type`, `url`)
              VALUES
                (%s, %s, %s)",
            $repositoryData['name'],
            $repositoryData['type'],
            $repositoryData['url']
        )->execute();
        if ($result === false) {
            return false;
        }
        return $this->db->getInsertId();
    }

    /**
     * Updates repository.
     *
     * @param array $repositoryData
     * @return bool
     */
    public function updateRepository(array $repositoryData)
    {
        if (!isset($repositoryData['id'])) {
            return false;
        }
        return $this->db->prepare(
            "UPDATE repositories
            SET `name` = %s,
              `type` = %s,
              `url` = %s
            WHERE id = %d",
            $repositoryData['name'],
            $repositoryData['type'],
            $repositoryData['url'],
            $repositoryData['id']
        )->execute();
    }

    /**
     * Deletes a repository.
     *
     * @param int $repositoryId
     * @return bool
     */
    public function deleteRepository($repositoryId)
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
     * @return array
     */
    public function getRepositoryData($repositoryId)
    {
        $repositoryId = (int)$repositoryId;
        if ($repositoryId === 0) {
            return [];
        }
        $repositoryData = $this->db->prepare("SELECT * FROM repositories WHERE id = %d", $repositoryId)->getResult(true);
        if (empty($repositoryData)) {
            return [];
        }
        return $repositoryData;
    }
}
