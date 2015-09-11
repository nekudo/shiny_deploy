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
     * @param string $repositoryUrl
     * @param string $targetPath
     * @return string
     */
    public function gitClone($repositoryUrl, $targetPath)
    {
        if (empty($repositoryUrl) || empty($targetPath)) {
            throw new \RuntimeException('Required argument missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        if (empty($repositoryUrl)) {
            throw new \RuntimeException('Repository url can not be empty.');
        }
        $response = $this->exec('clone --progress ' . $repositoryUrl . ' .');
        chdir($oldDir);
        return $response;
    }

    /**
     * Updates local git repository.
     *
     * @param string $repositoryUrl
     * @param string $targetPath
     * @return string
     */
    public function gitPull($repositoryUrl, $targetPath)
    {
        if (empty($repositoryUrl) || empty($targetPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        if (empty($repositoryUrl)) {
            throw new \RuntimeException('Repository URL can not be empty.');
        }
        $response = $this->exec('pull --progress');
        chdir($oldDir);
        return $response;
    }

    /**
     * Gets revision (latest commit hash) of local repository.
     *
     * @param string $repoPath
     * @param string $branch
     * @return bool|string
     */
    public function getLocalRepositoryRevision($repoPath, $branch)
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
        $revision = $this->exec('rev-parse ' . $branch);
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
     * Returns detailed diff for given file.
     *
     * @param string $repoPath
     * @param string $targetRevision
     * @param string $currentRevision
     * @param string $file
     * @return string
     */
    public function diffFile($repoPath, $targetRevision, $currentRevision, $file)
    {
        if (empty($repoPath) || empty($targetRevision) || empty($currentRevision) || empty($file)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $diff = $this->exec('diff ' . $currentRevision . ' ' .  $targetRevision . ' ' . $file);
        chdir($oldDir);
        return $diff;
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
     * @return array
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
     * Fetches list of local repository branches.
     *
     * @param string $repoPath
     * @return string
     */
    public function getLocalBranches($repoPath)
    {
        if (empty($repoPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (@chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $output = $this->exec('branch');
        chdir($oldDir);
        $output = trim($output);
        if (empty($output)) {
            $this->logger->warning('Could not fetch local repository branches: '  . $repoPath);
            return false;
        }
        $branches = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            $line = str_replace('*', '', $line);
            if (empty($line)) {
                continue;
            }
            array_push($branches, $line);
        }
        return $branches;
    }

    /**
     * Switch to a branch.
     *
     * @param string $repoPath
     * @param string $branch
     * @return bool
     */
    public function switchBranch($repoPath, $branch)
    {
        if (empty($repoPath) || empty($branch)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        if (strpos($branch, 'origin/') !== false) {
            $branch = str_replace('origin/', '', $branch);
        }
        $oldDir = getcwd();
        if (@chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }

        // check if already on correct branch
        $output = $this->exec('branch');
        if (strpos($output, '* ' . $branch) !== false) {
            chdir($oldDir);
            return true;
        }

        // switch branch
        $output = $this->exec('checkout ' . $branch);
        chdir($oldDir);
        $output = trim($output);
        if (strpos($output, 'Switched to a new branch') !== false) {
            return true;
        }
        if (strpos($output, 'Already on') !== false) {
            return true;
        }
        return false;
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
