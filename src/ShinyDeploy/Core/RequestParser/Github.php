<?php namespace ShinyDeploy\Core\RequestParser;

class Github implements RequestParser
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
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'GitHub') === false) {
            return false;
        }
        if (empty($_REQUEST['payload'])) {
            return false;
        }
        $payload = json_decode($_REQUEST['payload'], true);
        if (empty($payload)) {
            return false;
        }

        // fetch branch name
        if (empty($payload['ref'])) {
            return false;
        }
        $branchParts = explode('/', $payload['ref']);
        $this->parameters['branch'] = array_pop($branchParts);

        // fetch revision hash
        if (empty($payload['after'])) {
            return false;
        }
        $this->parameters['revision'] = $payload['after'];

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
