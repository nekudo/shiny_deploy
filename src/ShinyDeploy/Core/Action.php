<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;
use Slim\Slim;

class Action
{
    /** @var Config $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    /** @var  Slim $slim */
    protected $slim;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Injects slim object.
     *
     * @param Slim $slim
     */
    public function setSlim(Slim $slim)
    {
        $this->slim = $slim;
    }
}
