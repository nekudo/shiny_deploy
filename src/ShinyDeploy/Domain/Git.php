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

    /**
     * Clones git repository to local folder.
     *
     * @param string $idSource
     * @param string $targetPath
     * @return string
     */
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

    /**
     * Updates local git repository.
     *
     * @param string $idSource
     * @param string $targetPath
     * @return string
     */
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
     * Gets revision (latest commit hash) of local repository.
     *
     * @param string $repoPath
     * @return bool|string
     */
    public function getLocalRepositoryRevision($repoPath)
    {
        if (empty($repoPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        if (!file_exists($repoPath)) {
            throw new \RuntimeException('Repository path not found.');
        }
        $oldDir = getcwd();
        if (chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $revision = $this->exec('rev-parse HEAD');
        $revision = trim($revision);
        chdir($oldDir);
        if (preg_match('#[0-9a-f]{40}#', $revision) !== 1) {
            return false;
        }
        return $revision;
    }

    /**
     * Estimates diff between two revisions.
     *
     * @param string $repoPath
     * @param string $targetRevision
     * @param string $currentRevision
     * @return string
     */
    public function diff($repoPath, $targetRevision, $currentRevision)
    {
        if (empty($repoPath) || empty($targetRevision) || empty($currentRevision)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $changes = $this->exec('diff --name-status ' . $currentRevision . ' ' .  $targetRevision);
        chdir($oldDir);
        return $changes;
    }

    public function listFiles($repoPath)
    {
        if (empty($repoPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $changes = $this->exec('ls-files');
        chdir($oldDir);
        return $changes;
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
