<?php

namespace ShinyDeploy\Action\CliAction;

use ShinyDeploy\Action\CliAction;
use ShinyDeploy\Domain\Installer;

class Install extends CliAction
{
    /**
     * Executes all required installation steps for the application.
     *
     * @return void
     */
    public function __invoke() : void
    {
        try {
            $dbConfig = $this->config->get('db', null);
            if ($dbConfig === null || empty($dbConfig['host'])) {
                $this->error('Error: Database config missing or invalid. Check your config file.');
            }

            $installer = new Installer($this->config, $this->logger);
            if ($installer->checkDatabaseConnection($dbConfig) === false) {
                $this->error('Error: Could not connect to MySQL database. Please check config file.');
            }

            if ($installer->createTables() === false) {
                $this->error('Error: Could not create MySQL table. Check log for details.');
                exit;
            }

            // @todo create system user


            $this->success('Installation completed.');
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
