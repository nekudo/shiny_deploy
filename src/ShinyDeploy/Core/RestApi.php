<?php namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Exception;
use Noodlehaus\Config;
use ShinyDeploy\Action\StartApiJob;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Responder\RestApiResponder;

class RestApi
{
    /** @var Config $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    /** @var string $action */
    protected $action = '';

    /** @var string $apiKey */
    protected $apiKey = '';

    /** @var string $apiPassword */
    protected $apiPassword = '';

    /** @var RestApiResponder $responder */
    protected $responder;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->responder = new RestApiResponder($this->config, $this->logger);
        $this->parseRequest();
    }

    /**
     * Handles incomming REST API requests.
     */
    public function handleRequest()
    {
        $this->validateRequest();
        $this->executeRequest();
    }

    /**
     * Triggers gearman job as requested via API.
     */
    protected function executeRequest()
    {
        try {
            $jobName = strtolower($this->action);
            $jobName = 'api' . ucfirst($jobName);
            $action = new StartApiJob($this->config, $this->logger);
            $result = $action->__invoke($jobName, $this->apiKey, $this->apiPassword);
            if ($result === true) {
                $this->responder->respond('OK');
            }
            $this->responder->respondError('Could not start job.');
        } catch (Exception $e) {
            $this->logger->error(
                'API Error: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $this->responder->respondError($e->getMessage());
        }
    }

    /**
     * Validates request parameters.
     */
    protected function validateRequest()
    {
        if (empty($this->action)) {
            $this->responder->respondBadRequest('No action provided.');
        }
        if (preg_match('/^[a-z0-9]+$/i', $this->apiPassword) !== 1) {
            $this->responder->respondBadRequest('Invalid action name.');
        }
        if (empty($this->apiKey)) {
            $this->responder->respondBadRequest('API key missing.');
        }
        if (preg_match('/^[a-z0-9]+$/i', $this->apiPassword) !== 1) {
            $this->responder->respondBadRequest('Invalid API key.');
        }
        if (empty($this->apiPassword)) {
            $this->responder->respondBadRequest('API password missing.');
        }
        if (preg_match('/^[a-z0-9]+$/i', $this->apiPassword) !== 1) {
            $this->responder->respondBadRequest('Invalid API password.');
        }
        $auth = new Auth($this->config, $this->logger);
        $passwordIsValid = $auth->apiPasswordIsValid($this->apiPassword);
        if ($passwordIsValid === false) {
            $this->responder->respondBadRequest('Invalid API password.');
        }
    }

    /**
     * Sets known request parameters.
     */
    protected function parseRequest()
    {
        $this->action = (isset($_GET['a'])) ? trim($_GET['a']) : 'deploy';
        $this->apiKey = (isset($_GET['ak'])) ? trim($_GET['ak']) : '';
        $this->apiPassword = (isset($_GET['ap'])) ? trim($_GET['ap']) : '';
    }
}
