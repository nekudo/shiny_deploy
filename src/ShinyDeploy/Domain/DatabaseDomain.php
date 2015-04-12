<?php
namespace ShinyDeploy\Domain;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Core\Db;
use ShinyDeploy\Core\Domain;

class DatabaseDomain extends Domain
{
    protected $db;

    public function __construct(Config $config, Logger $logger)
    {
        $dbConfig = $config->get('db');
        if (empty($dbConfig)) {
            throw new \RuntimeException('Database configuration not set.');
        }
        $this->db = new Db($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);
    }
}
