<?php
namespace ShinyDeploy\Domain\Database;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Core\Db;
use ShinyDeploy\Core\Domain;
use Valitron\Validator;

class DatabaseDomain extends Domain
{
    /** @var \ShinyDeploy\Core\Db $db */
    protected Db $db;

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);

        // connect to database:
        $dbConfig = $config->get('db');
        if (empty($dbConfig)) {
            throw new \RuntimeException('Database configuration not set.');
        }
        $this->db = new Db($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);

        // add custom validation rules:
        $this->addCustomValidationRules();
    }

    /**
     * Adds custom validation rules to validator lib.
     *
     * @return void
     */
    private function addCustomValidationRules(): void
    {
        // add hostname validation:
        Validator::addRule(
            'hostname',
            function ($field, $value, array $params) {
                // lets first transform it to ascii
                $value = idn_to_ascii((string) $value);

                // valid chars check
                if (!preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $value)) {
                    return false;
                }

                // overall length check
                if (!preg_match('/^.{1,253}$/', $value)) {
                    return false;
                }

                // length of each label
                if (!preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $value)) {
                    return false;
                }

                return true;
            },
            'not valid.'
        );
    }
}
