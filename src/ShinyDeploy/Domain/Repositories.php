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
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        $result = $this->db->prepare(
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
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
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
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        return $repositoryData;
    }

    /**
     * Checks if URL responses with status 200.
     *
     * @param array $repositoryData
     * @return bool
     */
    public function checkUrl(array $repositoryData)
    {
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $repositoryData['url']);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        if (!empty($repositoryData['username'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $repositoryData['username'].':'.$repositoryData['password']);
        }
        $headers = curl_exec($ch);
        curl_close($ch);
        if (stripos($headers, 'HTTP/1.1 200') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Returns repository url after adding login credentials (if available).
     *
     * @param array $repositoryData
     * @return string
     */
    public function getCredentialsUrl(array $repositoryData)
    {
        $credentials = '';
        if (!empty($repositoryData['username'])) {
            $credentials .= $repositoryData['username'];
        }
        if (!empty($repositoryData['password'])) {
            $credentials .= ':' . $repositoryData['password'];
        }
        $url = str_replace('://', '://' . $credentials . '@', $repositoryData['url']);
        return $url;
    }
}
