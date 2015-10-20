<?php
namespace ShinyDeploy\Action\WsTriggerAction;

class StartGetDiff extends WsTriggerAction
{
    /**
     * This action is called by the websocket server to pass a deployment
     * job to a worker.
     *
     * @param $actionPayload
     * @return array
     */
    public function __invoke(array $actionPayload)
    {
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $actionPayload['clientId'] = $this->clientId;
        $payload = json_encode($actionPayload);
        $client->doBackground('getFileDiff', $payload);
        return true;
    }
}
