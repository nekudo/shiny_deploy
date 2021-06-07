<?php namespace ShinyDeploy\Action\ApiAction;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Exceptions\MissingDataException;

abstract class ApiAction extends Action
{
    /** @var string $apiKey */
    protected string $apiKey;

    /** @var string $apiPassword */
    protected string $apiPassword;

    abstract public function __invoke(array $requestParameters = []);

    /**
     * Sets API key.
     *
     * @param string $apiKey
     * @throws MissingDataException
     * @return void
     */
    public function setApiKey(string $apiKey): void
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
     * @return void
     */
    public function setApiPassword(string $apiPassword): void
    {
        if (empty($apiPassword)) {
            throw new MissingDataException('API password can not be empty');
        }
        $this->apiPassword = $apiPassword;
    }
}
