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
     * @param array $repositoryData
     * @param string $targetPath
     * @return string
     */
    public function gitClone(array $repositoryData, $targetPath)
    {
        if (empty($repositoryData) || empty($targetPath)) {
            throw new \RuntimeException('Required argument missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        if (empty($repositoryData['url'])) {
            throw new \RuntimeException('Repository url can not be empty.');
        }
        $response = $this->exec('clone --progress ' . $repositoryData['url'] . ' .');
        chdir($oldDir);
        return $response;
    }

    /**
     * Updates local git repository.
     *
     * @param array $repositoryData
     * @param string $targetPath
     * @return string
     */
    public function gitPull(array $repositoryData, $targetPath)
    {
        if (empty($repositoryData) || empty($targetPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        if (empty($repositoryData['url'])) {
            throw new \RuntimeException('Repository URL can not be empty.');
        }
        $response = $this->exec('pull --progress ' . $repositoryData['url']);
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

    /**
     * Lists files in repository.
     *
     * @param string $repoPath
     * @return string
     */
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
     * Fetches list of repositories remote branches.
     *
     * @param string $repoPath
     * @return string
     */
    public function getRemoteBranches($repoPath)
    {
        if (empty($repoPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (@chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $output = $this->exec('branch -r');
        chdir($oldDir);
        $output = trim($output);
        if (empty($output)) {
            $this->logger->warning('Could not fetch braches of repository: '  . $repoPath);
            return false;
        }
        $branches = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            // skip links/aliases:
            if (strpos($line, ' -> ') !== false) {
                continue;
            }
            $branches[] = [
                'id' => $line,
                'name' => str_replace('origin/', '', $line),
            ];
        }
        return $branches;
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
