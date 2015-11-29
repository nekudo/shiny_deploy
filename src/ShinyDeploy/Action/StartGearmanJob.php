<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Responder\WsLogResponder;

class StartGearmanJob extends Action
{
    /**
     * Starts a gearman job.
     *
     * @param string $jobName
     * @param string $clientId
     * @param array $jobPayload
     * @return boolean
     * @throws RuntimeException
     */
    public function __invoke($jobName, $clientId, array $jobPayload = [])
    {
        $this->authorize($clientId);

        try {
            if (empty($jobName) || empty($clientId)) {
                throw new RuntimeException('Required argument missing.');
            }

            $logResponder = new WsLogResponder($this->config, $this->logger);
            $logResponder->setClientId($clientId);
            $logResponder->log(
                'Received job request. Triggering job '.$jobName.'.',
                'info',
                'StartGearmanJobAction'
            );

            $client = new \GearmanClient;
            $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
            $jobPayload['clientId'] = $clientId;
            $jobPayload['token'] = $this->token;
            $payload = json_encode($jobPayload);
            $client->doBackground($jobName, $payload);
            return true;

        } catch (RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }
}
