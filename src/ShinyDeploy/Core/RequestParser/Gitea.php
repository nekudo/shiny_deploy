<?php namespace ShinyDeploy\Core\RequestParser;

class Gitea implements RequestParser
{
    /** @var array $parameters */
    protected array $parameters = [];

    /**
     * Parse useful information out of github post request.
     *
     * @return boolean
     */
    public function parseRequest(): bool
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'GiteaServer') === false) {
            return false;
        }
        $requestData = file_get_contents('php://input');
        if (empty($requestData)) {
            return false;
        }
        $requestParams = json_decode($requestData, true);
        if (empty($requestParams)) {
            return false;
        }

        // fetch branch name
        if (empty($requestParams['ref'])) {
            return false;
        }
        $branchParts = explode('/', $requestParams['ref']);
        $this->parameters['branch'] = array_pop($branchParts);

        // fetch revision hash
        if (empty($requestParams['after'])) {
            return false;
        }
        $this->parameters['revision'] = $requestParams['after'];

        return true;
    }

    /**
     * Returns parsed information.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
