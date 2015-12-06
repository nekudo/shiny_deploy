<?php namespace ShinyDeploy\Core\RequestParser;

use ShinyDeploy\Core\RequestParser\RequestParser;

class Bitbucket implements RequestParser
{
    /** @var array $parameters */
    protected $parameters = [];

    /**
     * Parse useful information out of bitbucket "post" request.
     *
     * @return boolean
     */
    public function parseRequest()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'Bitbucket') === false) {
            return false;
        }

        // Unfortunatly bitbucket does not send valid POST requests so we need this hack:
        $requestData = file_get_contents('php://input');
        if (empty($requestData)) {
            return false;
        }
        $requestParams = json_decode($requestData, true);
        if (empty($requestParams)) {
            return false;
        }

        // fetch branch name
        if (!empty($requestParams['push']['changes'][0]['new']['name'])) {
            $branchParts = explode('/', $requestParams['push']['changes'][0]['new']['name']);
            $this->parameters['branch'] = array_pop($branchParts);
        }

        // fetch revision hash
        if (!empty($requestParams['push']['changes'][0]['new']['target']['hash'])) {
            $this->parameters['revision'] = $requestParams['push']['changes'][0]['new']['target']['hash'];
        }

        return true;
    }

    /**
     * Returns parsed informations.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
