<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;

class Action
{
    /** @var Config $config */
    protected Config $config;

    /** @var Logger $logger */
    protected Logger $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }
}
