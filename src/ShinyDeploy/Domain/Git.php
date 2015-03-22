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
        $response = trim($response);
        if (strpos($response, 'git version') === false) {
            return false;
        }
        return $response;
    }

    public function gitClone($idSource, $targetPath)
    {
        if (empty($idSource) || empty($targetPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $repoUrl = $this->config->get('sources.'.$idSource.'.url', null);
        if (empty($repoUrl)) {
            throw new \RuntimeException('Could not get repository URL.');
        }
        $response = $this->exec('clone --progress ' . $repoUrl . ' .');
        chdir($oldDir);
        return $response;
    }

    public function gitPull($idSource, $targetPath)
    {
        if (empty($idSource) || empty($targetPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $repoUrl = $this->config->get('sources.'.$idSource.'.url', null);
        if (empty($repoUrl)) {
            throw new \RuntimeException('Could not get repository URL.');
        }
        $response = $this->exec('pull --progress ' . $repoUrl);
        chdir($oldDir);
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
        $command = escapeshellcmd($command) . ' 2>&1';
        $response = shell_exec($command);
        return $response;
    }
}
