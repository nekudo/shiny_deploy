<?php namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\InvalidTokenException;

class WsAction extends Action
{
    /**
     * @var string $clientId
     */
    protected $clientId = '';

    /**
     * @var string $token
     */
    protected $token = '';

    /**
     * Sets the client id.
     *
     * @param string $clientId
     * @return void
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * Sets the auth token.
     *
     * @param string $token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Checks if auth token is valid.
     *
     * @param string $clientId
     * @throws InvalidTokenException
     * @return void
     */
    public function authorize(string $clientId): void
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
