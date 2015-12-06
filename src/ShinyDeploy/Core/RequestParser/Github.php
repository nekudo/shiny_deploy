<?php namespace ShinyDeploy\Core\RequestParser;

use ShinyDeploy\Core\RequestParser\RequestParser;

class Github implements RequestParser
{
    /** @var array $parameters */
    protected $parameters = [];

    /**
     * Parse useful information out of github post request.
     *
     * @return boolean
     */
    public function parseRequest()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'GitHub') === false) {
            return false;
        }
        if (empty($_REQUEST['payload'])) {
            return false;
        }
        $payload = json_decode($_REQUEST['payload'], true);
        if (!empty($payload['ref'])) {
            $branchParts = explode('/', $payload['ref']);
            $this->parameters['branch'] = array_pop($branchParts);
        }
        if (!empty($payload['after'])) {
            $this->parameters['revision'] = $payload['after'];
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
