<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;

class StartGearmanJob extends Action
{
    /**
     * Starts a gearman job.
     *
     * @param string $jobName
     * @param string $clientId
     * @param array $jobPayload
     * @return boolean
     * @throws \RuntimeException
     */
    public function __invoke($jobName, $clientId, array $jobPayload = [])
    {
        try {
            if (empty($jobName) || empty($clientId)) {
                throw new \RuntimeException('Required argument missing.');
            }
            $client = new \GearmanClient;
            $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
            $jobPayload['clientId'] = $clientId;
            $payload = json_encode($jobPayload);
            $client->doBackground($jobName, $payload);
            return true;

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }
}