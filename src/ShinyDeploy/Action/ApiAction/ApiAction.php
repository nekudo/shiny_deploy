<?php namespace ShinyDeploy\Action\ApiAction;

use ShinyDeploy\Core\Action;

abstract class ApiAction extends Action
{
    /** @var string $apiKey */
    protected $apiKey;

    /** @var string $apiPassword */
    protected $apiPassword;

    abstract public function __invoke();

    /**
     * Sets API key.
     *
     * @param string $apiKey
     * @throws \InvalidArgumentException
     */
    public function setApiKey($apiKey)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key can not be empty.');
        }
        $this->apiKey = $apiKey;
    }

    /**
     * Sets API password.
     *
     * @param string $apiPassword
     * @throws \InvalidArgumentException
     */
    public function setApiPassword($apiPassword)
    {
        if (empty($apiPassword)) {
            throw new \InvalidArgumentException('API password can not be empty');
        }
        $this->apiPassword = $apiPassword;
    }
}
