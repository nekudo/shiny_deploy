<?php
namespace ShinyDeploy\Domain\Database;

use RuntimeException;
use ShinyDeploy\Domain\Deployment;
use ShinyDeploy\Traits\CryptableDomain;

class Deployments extends DatabaseDomain
{
    use CryptableDomain;

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

    /** @var array $encryptedFields Fields that are encrypted in database. */
    protected $encryptedFields = [
        'tasks',
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
     * Creates and returns a deployment object.
     *
     * @param int $deploymentId
     * @throws RuntimeException
     * @return Deployment
     */
    public function getDeployment($deploymentId)
    {
        $data = $this->getDeploymentData($deploymentId);
        if (empty($data)) {
            throw  new RuntimeException('Deployment not found in database.');
        }
        $deployment = new Deployment($this->config, $this->logger);
        $deployment->setEncryptionKey($this->encryptionKey);
        $deployment->init($data);
        return $deployment;
    }

    /**
     * Fetches list of deployments from database.
     *
     * @return array|bool
     */
    public function getDeployments()
    {
        $rows = $this->db->prepare("SELECT * FROM deployments ORDER BY `name`")->getResult(false);
        if (empty($rows)) {
            return $rows;
        }
        foreach ($rows as $i => $row) {
            $rowDecrypted = $this->decryptData($row, $this->encryptedFields);
            if ($rowDecrypted === false) {
                throw new RuntimeException('Data decryption failed.');
            }
            $rows[$i] = $rowDecrypted;
        }
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
        if (!isset($deploymentData['tasks'])) {
            $deploymentData['tasks'] = '';
        }
        $deploymentData = $this->encryptData($deploymentData, $this->encryptedFields);
        if ($deploymentData === false) {
            throw new RuntimeException('Data encryption failed.');
        }
        return $this->db->prepare(
            "INSERT INTO deployments
              (`name`, `repository_id`, `server_id`, `branch`, `target_path`, `tasks`)
              VALUES
                (%s, %d, %d, %s, %s, %s)",
            $deploymentData['name'],
            $deploymentData['repository_id'],
            $deploymentData['server_id'],
            $deploymentData['branch'],
            $deploymentData['target_path'],
            $deploymentData['tasks']
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
        $deploymentData = $this->encryptData($deploymentData, $this->encryptedFields);
        if ($deploymentData === false) {
            throw new RuntimeException('Data encryption failed.');
        }
        return $this->db->prepare(
            "UPDATE deployments
            SET `name` = %s,
              `repository_id` = %d,
              `server_id` = %d,
              `branch` = %s,
              `target_path` = %s,
              `tasks` = %s
            WHERE id = %d",
            $deploymentData['name'],
            $deploymentData['repository_id'],
            $deploymentData['server_id'],
            $deploymentData['branch'],
            $deploymentData['target_path'],
            $deploymentData['tasks'],
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
        // delete deployment logs:
        $this->db->prepare("DELETE FROM deployment_logs WHERE `deployment_id` = %d", $deploymentId)->execute();

        // delete deployment:
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
        $deploymentData = $this->decryptData($deploymentData, $this->encryptedFields);
        if ($deploymentData === false) {
            throw new RuntimeException('Data decryption failed.');
        }
        if (!empty($deploymentData['tasks'])) {
            $deploymentData['tasks'] = json_decode($deploymentData['tasks'], true);
        }
        return $deploymentData;
    }

    /**
     * Checks if another deployment with same server-id and target path exists.
     *
     * @param array $deploymentData
     * @return bool
     */
    public function targetExists(array $deploymentData)
    {
        $statement = "SELECT COUNT(id) FROM deployments WHERE `server_id` = %d AND `target_path` = %s";
        $cnt = $this->db->prepare($statement, $deploymentData['server_id'],$deploymentData['target_path'])
            ->getValue();
        return ($cnt > 0);
    }

    /**
     * Adds ids to tasks and returns data as json-encoded string.
     *
     * @param array $tasks
     * @return string
     */
    public function encodeDeploymentTasks(array $tasks)
    {
        // set task ids:
        foreach ($tasks as $i => $task) {
            if (!isset($task['id'])) {
                $tasks[$i]['id'] = $this->getRandomTaskId();
            }
        }

        // return tasks as json-encoded string:
        return json_encode($tasks);
    }

    /**
     * Generates a random string to use as task-id.
     *
     * @return string
     */
    protected function getRandomTaskId()
    {
        $randomId = '';
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < 6; $i++) {
            $randomId .= $characters[mt_rand(0, $max)];
        }
        return $randomId;
    }
}
