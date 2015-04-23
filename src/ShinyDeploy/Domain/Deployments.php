<?php
namespace ShinyDeploy\Domain;

class Deployments extends DatabaseDomain
{
    /** @var array $rules Validation rules */
    protected $rules = [
        'required' => [
            ['name'],
            ['repository_id'],
            ['server_id'],
            ['branch'],
            ['target_path'],
        ],
        'integer' => [
            ['repository_id'],
            ['server_id'],
        ],
        'lengthBetween' => [
            ['name', 1, 100],
            ['branch', 1, 100],
            ['target_path', 1, 200],
        ]
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
     * Fetches list of deployments from database.
     *
     * @return array|bool
     */
    public function getDeployments()
    {
        $rows = $this->db->prepare("SELECT * FROM deployments ORDER BY `name`")->getResult(false);
        return $rows;
    }

    /**
     * Stores new server in database.
     *
     * @param array $deploymentData
     * @return bool
     */
    public function addDeployment(array $deploymentData)
    {
        return $this->db->prepare(
            "INSERT INTO deployments
              (`name`, `repository_id`, `server_id`, `branch`, `target_path`)
              VALUES
                (%s, %d, %d, %s, %s)",
            $deploymentData['name'],
            $deploymentData['repository_id'],
            $deploymentData['server_id'],
            $deploymentData['branch'],
            $deploymentData['target_path']
        )->execute();
    }

    /**
     * Updates deployment.
     *
     * @param array $deploymentData
     * @return bool
     */
    public function updateDeployment(array $deploymentData)
    {
        if (!isset($deploymentData['id'])) {
            return false;
        }
        return $this->db->prepare(
            "UPDATE deployments
            SET `name` = %s,
              `repository_id` = %d,
              `server_id` = %d,
              `branch` = %s,
              `target_path` = %s
            WHERE id = %d",
            $deploymentData['name'],
            $deploymentData['repository_id'],
            $deploymentData['server_id'],
            $deploymentData['branch'],
            $deploymentData['target_path'],
            $deploymentData['id']
        )->execute();
    }

    /**
     * Deletes a deployment.
     *
     * @param int $deploymentId
     * @return bool
     */
    public function deleteDeployment($deploymentId)
    {
        $deploymentId = (int)$deploymentId;
        if ($deploymentId === 0) {
            return false;
        }
        return $this->db->prepare("DELETE FROM deployments WHERE `id` = %d LIMIT 1", $deploymentId)->execute();
    }

    /**
     * Fetches deployment data.
     *
     * @param int $deploymentId
     * @return array
     */
    public function getDeploymentData($deploymentId)
    {
        $deploymentId = (int)$deploymentId;
        if ($deploymentId === 0) {
            return [];
        }
        $deploymentData = $this->db->prepare("SELECT * FROM deployments WHERE `id` = %d", $deploymentId)
            ->getResult(true);
        if (empty($deploymentData)) {
            return [];
        }
        return $deploymentData;
    }
}
