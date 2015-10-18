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

    /** @var array $data */
    protected $data = [];

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function init(array $data)
    {
        $this->data = $data;
    }
}
