<?php
namespace ShinyDeploy\Domain\Database;

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
     */
    public function logDeploymentStart($deploymentId, $deploymentType)
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
     */
    public function logDeploymentSuccess($deploymentLogId)
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
     */
    public function logDeploymentError($deploymentLogId)
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
     * @return bool
     * @throws MissingDataException
     */
    protected function logDeploymentEnd($deploymentLogId, $result)
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
     * @return array
     * @throws MissingDataException
     */
    public function getDeploymentLogs($deploymentId)
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
     * @return bool
     */
    public function clearLogs($deploymentId)
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
