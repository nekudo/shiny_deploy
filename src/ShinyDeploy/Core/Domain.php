<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;

class Domain
{
    /** @var Config $config */
    protected Config $config;

    /** @var Logger $logger */
    protected Logger $logger;

    /** @var array $data */
    protected array $data = [];

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
    public function init(array $data): void
    {
        $this->data = $data;
    }
}
