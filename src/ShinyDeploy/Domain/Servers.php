<?php
namespace ShinyDeploy\Domain;

use Apix\Log\Logger;
use Noodlehaus\Config;

class Servers extends DatabaseDomain
{
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Fetches list of servers from database.
     *
     * @return array|bool
     */
    public function getServers()
    {
        $rows = $this->db->prepare("SELECT * FROM servers ORDER BY `name`")->getResult(false);
        return $rows;
    }
}
