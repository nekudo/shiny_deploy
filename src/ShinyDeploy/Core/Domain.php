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

    /**
     * Sets data.
     *
     * @param array $data
     */
    public function init(array $data) : void
    {
        $this->data = $data;
    }
}
