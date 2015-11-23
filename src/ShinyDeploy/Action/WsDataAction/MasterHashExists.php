<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class MasterHashExists extends WsDataAction
{
    /**
     * Checks if master-password hash was already set.
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        $auth = new Auth($this->config, $this->logger);
        $masterHash = $auth->getMasterHash();
        $payload = ['hashExists' => false];
        if ($masterHash !== false) {
            $payload['hashExists'] = true;
        }
        $this->responder->setPayload($payload);
        return true;
    }
}
