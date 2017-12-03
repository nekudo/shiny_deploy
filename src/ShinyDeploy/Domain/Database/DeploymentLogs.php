<?php
namespace ShinyDeploy\Domain\Database;

use ShinyDeploy\Exceptions\DatabaseException;
use ShinyDeploy\Exceptions\MissingDataException;

class DeploymentLogs extends DatabaseDomain
{
    /**
     * Logs start/request of a deployment.
     *
     * @param int $deploymentId
     * @param string $deploymentType
     * @return int
     * @throws MissingDataException
     * @throws DatabaseException
     */
    public function logDeploymentStart(int $deploymentId, string $deploymentType) : int
    {
        if (empty($deploymentId)) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }
        if (empty($deploymentType)) {
            throw new MissingDataException('DeploymentType can not be empty.');
        }
        $statement = "INSERT INTO deployment_logs (`deployment_id`, `deployment_type`, `request_time`)
            VALUES (%d, %s, NOW())";
        $queryResult = $this->db->prepare($statement, $deploymentId, $deploymentType)->execute();
        if ($queryResult === false) {
            return 0;
        }
        return $this->db->getInsertId();
    }

    /**
     * Logs deployment end with type success.
     *
     * @param int $deploymentLogId
     * @return bool
     * @throws MissingDataException
     * @throws DatabaseException
     */
    public function logDeploymentSuccess(int $deploymentLogId) : bool
    {
        if (empty($deploymentLogId)) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }
        return $this->logDeploymentEnd($deploymentLogId, 'success');
    }

    /**
     * Logs deployment end with type error.
     *
     * @param int $deploymentLogId
     * @return bool
     * @throws MissingDataException
     * @throws DatabaseException
     */
    public function logDeploymentError(int $deploymentLogId) : bool
    {
        if (empty($deploymentLogId)) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }
        return $this->logDeploymentEnd($deploymentLogId, 'error');
    }

    /**
     * Log completion of deployment.
     *
     * @param int $deploymentLogId
     * @param string $result
     * @throws MissingDataException
     * @throws DatabaseException
     * @return bool
     */
    protected function logDeploymentEnd(int $deploymentLogId, string $result) : bool
    {
        if (empty($deploymentLogId)) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }
        if (empty($result)) {
            throw new MissingDataException('Result can not be empty');
        }
        $statement = "UPDATE deployment_logs
            SET
                `result` = %s,
                `end_time` = NOW()
            WHERE id = %d";
        return $this->db->prepare($statement, $result, $deploymentLogId)->execute();
    }



    /**
     * Fetches list of deployment logs for given deployment id.
     *
     * @param int $deploymentId
     * @throws MissingDataException
     * @throws DatabaseException
     * @return array
     */
    public function getDeploymentLogs(int $deploymentId) : array
    {
        if (empty($deploymentId)) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }

        // clear old logs:
        $this->clearLogs($deploymentId);

        // fetch logs:
        $statement = "SELECT dl.id,
                dl.deployment_type,
                dl.result,
                dl.request_time,
                TIMESTAMPDIFF(SECOND, dl.request_time, dl.end_time) AS duration
            FROM deployment_logs dl
            WHERE dl.deployment_id = %d
            ORDER by dl.id DESC";
        $rows = $this->db->prepare($statement, $deploymentId)->getResult(false);
        if ($rows === false) {
            return [];
        }
        return $rows;
    }

    /**
     * Removes logs keeping only a limit definded in settings.
     *
     * @param int $deploymentId
     * @throws DatabaseException
     * @return bool
     */
    public function clearLogs(int $deploymentId) : bool
    {
        $keep = (int) $this->config->get('logging.maxDeploymentLogs', 50);
        $statement = "DELETE FROM `deployment_logs`
            WHERE id <= (
                SELECT id
                FROM (
                    SELECT id
                    FROM `deployment_logs`
                    WHERE `deployment_id` = %d
                    ORDER BY id DESC
                    LIMIT 1 OFFSET %d
                ) tmp1
            )";
        return $this->db->prepare($statement, $deploymentId, $keep)->execute();
    }
}
