<?php namespace ShinyDeploy\Core\RequestParser;

interface RequestParser
{
    /**
     * Tries to get additional parameters from request.
     *
     * @return bool True if request could be parsed false otherwise.
     */
    public function parseRequest() : bool;

    public function getParameters() : array;
}
