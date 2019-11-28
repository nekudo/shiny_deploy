<?php

namespace ShinyDeploy\Action\CliAction;

use ShinyDeploy\Action\CliAction;
use ShinyDeploy\Domain\Database\Auth;
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
            $this->line('Testing connection to database...');
            $dbConfig = $this->config->get('db', null);
            if ($dbConfig === null || empty($dbConfig['host'])) {
                $this->error('Error: Database config missing or invalid. Check your config file.');
            }

            $installer = new Installer($this->config, $this->logger);
            if ($installer->checkDatabaseConnection($dbConfig) === false) {
                $this->error('Error: Could not connect to MySQL database. Please check config file.');
            }

            $this->line('Creating mysql tables...');
            if ($installer->createTables() === false) {
                $this->error('Error: Could not create MySQL table. Check log for details.');
                exit;
            }

            $this->line('Creating system user...');
            $this->info('ATTENTION: Please provide a strong password for your system account!');
            $password = $this->requestUserInput('Password: ', true);
            $passwordRepeat = $this->requestUserInput('Repeat: ', true);
            if (empty($password)) {
                $this->error('Error: System password can not be empty.');
                exit;
            }
            if ($password !== $passwordRepeat) {
                $this->error('Error: Passwords do not match.');
                exit;
            }

            $auth = new Auth($this->config, $this->logger);
            $result = $auth->createSystemUser($password);
            if ($result === false) {
                $this->error('Error: Could not create system user. Check log for details.');
                exit;
            }

            $this->success('Installation completed.');
            $this->line('Tip: You should create a new user using the "user-add" command.');
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
