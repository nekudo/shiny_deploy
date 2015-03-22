<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;

class StartDeploy extends Action
{
    /**
     * This action is called by the websocket server to pass a deployment
     * job to a worker.
     *
     * @param $params
     * @return array
     */
    public function __invoke($params)
    {
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $payload = json_encode($params);
        $client->doBackground('deploy', $payload);
        return true;
    }
}
