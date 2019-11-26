<?php

declare(strict_types=1);

namespace ShinyDeploy\Action\CliAction;

use ShinyDeploy\Action\CliAction;
use ShinyDeploy\Domain\Database\Auth;

class ChangeUserPassword extends CliAction
{
    /**
     * Changes a users password.
     *
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     */
    public function __invoke()
    {
        $auth = new Auth($this->config, $this->logger);

        // request system password
        $this->info('System password is required to perform this action.');
        $systemPassword = $this->requestUserInput('Please enter system password: ', true);
        if (empty($systemPassword)) {
            $this->error('Error: System password can not be empty.');
            exit;
        }
        $expectedHash = $auth->getPasswordHashByUsername('system');
        $actualHash = hash('sha256', $systemPassword);
        if ($expectedHash !== $actualHash) {
            $this->error('Error: Incorrect system password.');
            exit;
        }

        // request username
        $this->lb();
        $username = $this->requestUserInput('Username to update: ');
        if (empty($username)) {
            $this->error('Error: Username can not be empty.');
            exit;
        }
        if ($auth->userExists($username) === false) {
            $this->error('Error: Username not found in database.');
            exit;
        }

        // request new password
        $password = $this->requestUserInput('New password: ', true);
        $passwordConfirm = $this->requestUserInput('Repeat new password: ', true);
        if (empty($password)) {
            $this->error('Error: Password can not be empty.');
            exit;
        }
        if ($password !== $passwordConfirm) {
            $this->error('Error: The passwords do not match.');
            exit;
        }

        // update password
        $systemKey = $auth->getEncryptionKeyByUsernameAndPassword('system', $systemPassword);
        $result = $auth->updateUserPassword($username, $password, $systemKey);
        if ($result === false) {
            $this->error('Password change failed. Check logfile for details.');
            exit;
        }

        $this->success('Password successfully updated.');
    }
}
