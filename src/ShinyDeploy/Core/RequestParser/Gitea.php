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
     * Returns parsed information.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
