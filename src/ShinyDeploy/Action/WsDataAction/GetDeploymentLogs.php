<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\DeploymentLogs;
use ShinyDeploy\Exceptions\MissingDataException;

class GetDeploymentLogs extends WsDataAction
{
    /**
     * Fetches list of deployment logs.
     *
     * @param array $actionPayload
     * @return bool
     */
    public function __invoke(array $actionPayload)
    {
        $this->authorize($this->clientId);

        if (empty($actionPayload['deploymentId'])) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }

        $deploymentId = (int) $actionPayload['deploymentId'];
        $deploymentLogs = new DeploymentLogs($this->config, $this->logger);
        $logData = $deploymentLogs->getDeploymentLogs($deploymentId);
        $this->responder->setPayload($logData);
        return true;
    }
}
