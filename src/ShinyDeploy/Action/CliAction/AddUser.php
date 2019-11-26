<?php

declare(strict_types=1);

namespace ShinyDeploy\Action\CliAction;

use ShinyDeploy\Action\CliAction;
use ShinyDeploy\Domain\Database\Auth;

class AddUser extends CliAction
{
    /**
     * Adds new user to database.
     *
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     */
    public function __invoke()
    {
        // request system password
        $this->info('To create a new user system password is required.');
        $systemPassword = $this->requestUserInput('Please enter system password: ', true);
        if (empty($systemPassword)) {
            $this->error('Error: System password can not be empty.');
            exit;
        }

        $auth = new Auth($this->config, $this->logger);
        $expectedHash = $auth->getPasswordHashByUsername('system');
        $actualHash = hash('sha256', $systemPassword);
        if ($expectedHash !== $actualHash) {
            $this->error('Error: Incorrect system password.');
            exit;
        }

        // request details for new user
        $this->lb();
        $this->line('You can now enter the credentials for the new user.');
        $username = $this->requestUserInput('Username: ');
        $password = $this->requestUserInput('Password: ', true);
        $username = trim($username);
        $password = trim($password);
        if (empty($username) || empty($password)) {
            $this->error('Error: Username and password can not be empty.');
            exit;
        }

        // check if user already exists
        if ($auth->userExists($username)) {
            $this->error('Error: User already exists.');
            exit;
        }

        // create the new user
        $systemKey = $auth->getEncryptionKeyByUsernameAndPassword('system', $systemPassword);
        $result = $auth->createUser($username, $password, $systemKey);
        if ($result === false) {
            $this->error('Error: Could not create new user. Check logfile for details.');
            exit;
        }

        $this->success('User successfully created.');
    }
}
