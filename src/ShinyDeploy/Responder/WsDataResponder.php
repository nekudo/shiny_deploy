<?php
namespace ShinyDeploy\Responder;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Core\Responder;

class WsDataResponder extends Responder
{
    protected $callbackId;

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Sets callback id.
     *
     * @param string $callbackId
     * @return bool
     */
    public function setCallbackId($callbackId)
    {
        if (empty($callbackId)) {
            return false;
        }
        $this->callbackId = $callbackId;
        return true;
    }
}
