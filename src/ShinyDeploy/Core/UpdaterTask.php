<?php

namespace ShinyDeploy\Core;

use Noodlehaus\Config;
use Apix\Log\Logger;

abstract class UpdaterTask
{
    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * @var Logger $logger
     */
    protected Logger $logger;

    /**
     * @var Db $db
     */
    protected Db $db;

    public function __construct(Config $config, Logger $logger, Db $db)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Check if task needs to be executed.
     *
     * @return bool
     */
    abstract public function needsExecution(): bool;

    /**
     * Executes task.
     *
     * @return void
     */
    abstract public function __invoke(): void;
}
