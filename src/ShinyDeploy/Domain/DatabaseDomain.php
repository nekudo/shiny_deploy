<?php
namespace ShinyDeploy\Domain;

use Apix\Log\Logger;
use GG\Hostname;
use Noodlehaus\Config;
use ShinyDeploy\Core\Db;
use ShinyDeploy\Core\Domain;
use Valitron\Validator;

class DatabaseDomain extends Domain
{
    protected $db;

    public function __construct(Config $config, Logger $logger)
    {
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
     */
    private function addCustomValidationRules()
    {
        // add hostname validation:
        Validator::addRule(
            'hostname',
            function ($field, $value, array $params) {
                $realWorld = (isset($params[0]) && $params[0] === true) ? true : false;

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
