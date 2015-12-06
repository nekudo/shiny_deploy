<?php namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;

class StartApiJob extends Action
{
    /**
     * Starts a gearman job requested by rest api.
     *
     * @param string $jobName
     * @param string $apiKey
     * @param string $apiPassword
     * @param array $requestParameters
     * @return boolean
     * @throws RuntimeException
     */
    public function __invoke($jobName, $apiKey, $apiPassword, array $requestParameters = [])
    {
        $actionClassName = ucfirst(str_replace('api', '', $jobName));
        if (!class_exists('\ShinyDeploy\Action\ApiAction\\' . $actionClassName)) {
            throw new \RuntimeException('Invalid API action requested.');
        }
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $jobPayload = [
            'apiKey' => $apiKey,
            'apiPassword' => $apiPassword,
            'requestParameters' => $requestParameters,
        ];
        $payload = json_encode($jobPayload);
        $client->doBackground($jobName, $payload);
        return true;
    }
}
