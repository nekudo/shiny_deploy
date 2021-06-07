<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\DeploymentLogs;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\WsLogResponder;

class GetDeploymentLogs extends WsDataAction
{
    /**
     * Fetches list of deployment logs.
     *
     * @param array $actionPayload
     * @return bool
     * @throws MissingDataException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ZMQException
     */
    public function __invoke(array $actionPayload): bool
    {
        $this->authorize($this->clientId);

        if (empty($actionPayload['deploymentId'])) {
            throw new MissingDataException('DeploymentId can not be empty.');
        }

        // init responder:
        $logResponder = new WsLogResponder($this->config, $this->logger);
        $logResponder->setClientId($this->clientId);

        // get latest deployments:
        $logResponder->log('Fetching latest deployments...');
        $deploymentId = (int) $actionPayload['deploymentId'];
        $deploymentLogs = new DeploymentLogs($this->config, $this->logger);
        $logData = $deploymentLogs->getDeploymentLogs($deploymentId);
        if (empty($logData)) {
            $logResponder->info('No deployment logs found.');
        }
        $this->responder->setPayload($logData);
        return true;
    }
}
