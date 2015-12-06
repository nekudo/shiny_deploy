<?php namespace ShinyDeploy\Core\RequestParser;

use ShinyDeploy\Core\RequestParser\RequestParser;

class Bitbucket implements RequestParser
{
    /** @var array $parameters */
    protected $parameters = [];

    public function parseRequest()
    {
        return false;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
