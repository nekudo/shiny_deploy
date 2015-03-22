<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;

class Domain
{
    /** @var Config $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    /** @var string $clientId */
    protected $clientId;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Sets client-id
     *
     * @param string $clientId
     * @return bool
     */
    public function setClientId($clientId)
    {
        if (empty($clientId)) {
            return false;
        }
        $this->clientId = $clientId;
        return true;
    }
}
