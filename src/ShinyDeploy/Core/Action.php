<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;

class Action
{
    /** @var Config $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }
}
