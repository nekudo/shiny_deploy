<?php namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\ClassNotFoundException;
use ShinyDeploy\Exceptions\ShinyDeployException;
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

    /** @var array $requestParams */
    protected $requestParams = [];

    /** @var RestApiResponder $responder */
    protected $responder;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->responder = new RestApiResponder($this->config, $this->logger);
    }

    /**
     * Handles incoming REST API requests.
     *
     * @return void
     */
    public function handleRequest() : void
    {
        try {
            $this->parseRequest();
            $this->validateRequest();
            $this->executeRequest();
        } catch (ShinyDeployException $e) {
            $this->logger->error(
                'API Error: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $this->responder->respondError($e->getMessage());
        }
    }

    /**
     * Triggers Gearman job as requested via API.
     *
     * @return void
     * @throws ClassNotFoundException
     */
    protected function executeRequest() : void
    {
        $jobName = strtolower($this->action);
        if ($this->triggerBackgroundJob($jobName) === true) {
            $this->responder->respond('OK');
        } else {
            $this->responder->respondError('Could not start job.');
        }
    }

    /**
     * Validates request parameters.
     *
     * @return void
     */
    protected function validateRequest() : void
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
        $passwordIsValid = $auth->apiPasswordIsValid($this->apiKey, $this->apiPassword);
        if ($passwordIsValid === false) {
            $this->responder->respondBadRequest('Invalid API password.');
        }
    }

    /**
     * Sets known request parameters.
     *
     * @return void
     * @throws ClassNotFoundException
     */
    protected function parseRequest() : void
    {
        $this->action = (isset($_REQUEST['a'])) ? trim($_REQUEST['a']) : 'deploy';
        $this->apiKey = (isset($_REQUEST['ak'])) ? trim($_REQUEST['ak']) : '';
        $this->apiPassword = (isset($_REQUEST['ap'])) ? trim($_REQUEST['ap']) : '';
        $this->parseRequestParameters();
    }

    /**
     * Will trigger all activated request-parser to get additonal plattform dependent
     * parameters from request.
     *
     * @throws ClassNotFoundException
     * @return bool
     */
    protected function parseRequestParameters() : bool
    {
        $parserNames = $this->config->get('api.requestParser');
        if (empty($parserNames)) {
            return true;
        }
        foreach ($parserNames as $parserName) {
            $parserClass = '\ShinyDeploy\Core\RequestParser\\' . ucfirst(strtolower($parserName));
            if (!class_exists($parserClass)) {
                throw new ClassNotFoundException('Request parser not found. (' . $parserName . ')');
            }
            /** @var \ShinyDeploy\Core\RequestParser\RequestParser $parser */
            $parser = new $parserClass;
            if ($parser->parseRequest() === true) {
                $this->requestParams = $parser->getParameters();
                return true;
            }
        }
        return true;
    }

    /**
     * Triggers execution of a background job.
     *
     * @param string $action
     * @return boolean
     * @throws ClassNotFoundException
     */
    protected function triggerBackgroundJob(string $action) : bool
    {
        $actionClassName = ucfirst($action);
        $jobName = 'api' . $actionClassName;
        if (!class_exists('\ShinyDeploy\Action\ApiAction\\' . $actionClassName)) {
            throw new ClassNotFoundException('Invalid API action requested.');
        }
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $jobPayload = [
            'apiKey' => $this->apiKey,
            'apiPassword' => $this->apiPassword,
            'requestParameters' => $this->requestParams,
        ];
        $payload = json_encode($jobPayload);
        $client->doBackground($jobName, $payload);
        return true;
    }
}
