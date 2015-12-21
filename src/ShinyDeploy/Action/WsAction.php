<?php namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\InvalidTokenException;

class WsAction extends Action
{
    protected $clientId = '';
    
    protected $token = '';

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function authorize($clientId)
    {
        if (empty($this->token)) {
            throw new InvalidTokenException('Invalid token.');
        }
        $auth = new Auth($this->config, $this->logger);
        $validationResult = $auth->validateToken($this->token, $clientId);
        if ($validationResult !== true) {
            throw new InvalidTokenException('Invalid Token');
        }
    }
}
