<?php

namespace ShinyDeploy\Domain;

use ShinyDeploy\Core\Domain;

class Git extends Domain
{
    /**
     * Gets git version. Used to check if git is available.
     *
     * @return bool|string
     */
    public function getVersion()
    {
        $response = $this->exec('--version');
        if (strpos($response, 'git version') === false) {
            return false;
        }
        return $response;
    }

    /**
     * Executes a git command and returns response.
     *
     * @param $command
     * @return string
     */
    protected function exec($command)
    {
        $command = 'git ' . $command;
        $command = escapeshellcmd($command);
        $response = shell_exec($command);
        return $response;
    }
}
