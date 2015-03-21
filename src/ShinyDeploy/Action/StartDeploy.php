<?php
namespace ShinyDeploy\Action;

class StartDeploy
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
        $client->addServer('127.0.0.1', 4730);
        $payload = json_encode($params);
        $client->doBackground('deploy', $payload);
        return true;
    }
}
