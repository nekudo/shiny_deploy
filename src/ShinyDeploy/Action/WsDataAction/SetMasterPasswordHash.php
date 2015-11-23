<?php namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;

class SetMasterPasswordHash extends WsDataAction
{
    /**
     * Checks if master-password hash was already set.
     *
     * @param mixed $actionPayload
     * @return bool
     */
    public function __invoke($actionPayload)
    {
        if (empty($actionPayload['password']) || empty($actionPayload['password_verify'])) {
            $this->responder->setError('Password can not be empty.');
            return false;
        }
        if ($actionPayload['password'] !== $actionPayload['password_verify']) {
            $this->responder->setError('Passwords do not match.');
            return false;
        }

        $auth = new Auth($this->config, $this->logger);
        $result = $auth->setMasterPasswordHash($actionPayload['password']);
        if ($result === false) {
            $this->responder->setError('Could not save master-password.');
            return false;
        }

        // return success
        $this->responder->setPayload(['success' => true]);
        return true;
    }
}
