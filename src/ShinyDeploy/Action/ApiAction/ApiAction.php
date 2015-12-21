<?php namespace ShinyDeploy\Action\ApiAction;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Exceptions\MissingDataException;

abstract class ApiAction extends Action
{
    /** @var string $apiKey */
    protected $apiKey;

    /** @var string $apiPassword */
    protected $apiPassword;

    abstract public function __invoke(array $requestParameters = []);

    /**
     * Sets API key.
     *
     * @param string $apiKey
     * @throws MissingDataException
     */
    public function setApiKey($apiKey)
    {
        if (empty($apiKey)) {
            throw new MissingDataException('API key can not be empty.');
        }
        $this->apiKey = $apiKey;
    }

    /**
     * Sets API password.
     *
     * @param string $apiPassword
     * @throws MissingDataException
     */
    public function setApiPassword($apiPassword)
    {
        if (empty($apiPassword)) {
            throw new MissingDataException('API password can not be empty');
        }
        $this->apiPassword = $apiPassword;
    }
}
