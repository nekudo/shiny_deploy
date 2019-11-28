<?php

declare(strict_types=1);

namespace ShinyDeploy\Domain;

use ShinyDeploy\Core\Db;
use ShinyDeploy\Core\Domain;
use ShinyDeploy\Exceptions\DatabaseException;

class Installer extends Domain
{
    /**
     * Checks connection to mysql database.
     *
     * @param array $dbConfig
     * @return bool
     */
    public function checkDatabaseConnection(array $dbConfig): bool
    {
        try {
            $db = new Db($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);

            return true;
        } catch (DatabaseException $e) {
            return false;
        }
    }

    /**
     * Creates mysql tables required for the application.
     * Hint: Uses queries from db_structure.sql in project root.
     *
     * @return bool
     * @throws DatabaseException
     */
    public function createTables(): bool
    {
        // check if db-structure file exists
        $pathToDbStructureFile = PROJECT_ROOT . 'db_structure.sql';
        if (!file_exists($pathToDbStructureFile)) {
            $this->logger->error('Could not find file db_structure.sql in project root.');
            return false;
        }

        // check if tables exist
        $dbConfig = $this->config->get('db');
        $db = new Db($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);
        $existingTables = $db->prepare('SHOW TABLES')->getResult(true);
        if (count($existingTables) > 0) {
            $this->logger->error('Could not create tables because database is not empty.');
            unset($db);
            return false;
        }

        // create the tables
        $mysqli = new \mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);
        if ($mysqli->connect_errno) {
            $this->logger->error('Could not connect to mysql database.');
            return false;
        }

        $statements = file_get_contents($pathToDbStructureFile);
        $firstStatementResult = $mysqli->multi_query($statements);
        if ($firstStatementResult === false) {
            $this->logger->error('Error during create tables query. Tip: Check db_structure.sql file.');
            return false;
        }
        unset($mysqli);
        sleep(1);

        // check number of created tables
        $existingTables = $db->prepare('SHOW TABLES')->getResult(false);
        $existingTablesCount = count($existingTables);
        $expectedTablesCount = substr_count($statements, 'CREATE TABLE');
        if ($existingTablesCount !== $expectedTablesCount) {
            $this->logger->error(sprintf(
                'Not all tables were created. Tables created: %d Tables expected: %d',
                $existingTablesCount,
                $expectedTablesCount
            ));
            return false;
        }

        return true;
    }
}
