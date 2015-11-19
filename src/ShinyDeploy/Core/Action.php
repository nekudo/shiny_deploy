<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\InvalidTokenException;

class Action
{
    /** @var Config $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    protected $token = '';

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
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
