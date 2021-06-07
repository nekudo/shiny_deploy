<?php namespace ShinyDeploy\Core\RequestParser;

class Bitbucket implements RequestParser
{
    /** @var array $parameters */
    protected array $parameters = [];

    /**
     * Parse useful information out of bitbucket "post" request.
     *
     * @return bool
     */
    public function parseRequest(): bool
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'Bitbucket') === false) {
            return false;
        }

        // Unfortunately bitbucket does not send valid POST requests so we need this hack:
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
     * Returns parsed information.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
