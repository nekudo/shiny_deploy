<?php

namespace ShinyDeploy\Domain;

use ShinyDeploy\Core\Domain;
use ShinyDeploy\Exceptions\GitException;
use ShinyDeploy\Exceptions\MissingDataException;

class Git extends Domain
{
    /**
     * Gets git version. Used to check if git is available.
     *
     * @return string
     */
    public function getVersion(): string
    {
        try {
            $response = $this->exec('--version');
            return trim($response);
        } catch (GitException $e) {
            $this->logger->critical('Git binary seems to be missing on system.');
            return '';
        }
    }

    /**
     * Clones git repository to local folder.
     *
     * @param string $repositoryUrl
     * @param string $targetPath
     * @return string
     * @throws \RuntimeException
     * @throws MissingDataException
     * @throws GitException
     */
    public function gitClone(string $repositoryUrl, string $targetPath): string
    {
        if (empty($repositoryUrl) || empty($targetPath)) {
            throw new MissingDataException('Required argument missing.');
        }
        $oldDir = getcwd();
        if (chdir($targetPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }

        // clone the repo:
        $response = $this->exec('clone --progress ' . $repositoryUrl . ' .');

        // set git user and email to avoid error messages:
        $this->exec(sprintf('config user.name "%s"', $this->config->get('git.name')));
        $this->exec(sprintf('config user.email "%s"', $this->config->get('git.email')));

        chdir($oldDir);
        return $response;
    }

    /**
     * Updates local git repository.
     *
     * @param string $repositoryUrl
     * @param string $targetPath
     * @return string
     * @throws \RuntimeException
     * @throws GitException
     */
    public function gitPull(string $repositoryUrl, string $targetPath): string
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
     * @return string
     * @throws \RuntimeException
     */
    public function getLocalRepositoryRevision(string $repoPath, string $branch): string
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

        try {
            $revision = $this->exec('rev-parse ' . $branch);
            $revision = trim($revision);
            chdir($oldDir);
            if (preg_match('#[0-9a-f]{40}#', $revision) !== 1) {
                return '';
            }
            return $revision;
        } catch (GitException $e) {
            $this->logger->error('Could not estimate local git revision. ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Gets revision (latest commit hash) of remote repository.
     *
     * @param string $repoPath
     * @param string $repoUrl
     * @param string $branch
     * @return string
     * @throws \RuntimeException
     */
    public function getRemoteRepositoryRevision(string $repoPath, string $repoUrl, string $branch): string
    {
        if (empty($repoPath) || empty($repoUrl) || empty($branch)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        if (strpos($branch, 'origin/') !== false) {
            $branch = str_replace('origin/', '', $branch);
        }

        try {
            $revision = $this->exec('ls-remote ' . $repoUrl . ' ' . $branch);
            chdir($oldDir);
            $revision = substr($revision, 0, 40);
            $revision = trim($revision);
            $revision = preg_replace('#[^0-9a-f]#', '', $revision);
            if (preg_match('#[0-9a-f]{40}#', $revision) !== 1) {
                return '';
            }
            return $revision;
        } catch (GitException $e) {
            $this->logger->error('Could not estimate remote git revision. ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Estimates diff between two revisions.
     *
     * @param string $repoPath
     * @param string $targetRevision
     * @param string $currentRevision
     * @return string
     * @throws \RuntimeException
     * @throws GitException
     */
    public function diff(string $repoPath, string $targetRevision, string $currentRevision): string
    {
        if (empty($repoPath) || empty($targetRevision) || empty($currentRevision)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        $changes = $this->exec('diff --name-status --no-renames ' . $currentRevision . ' ' .  $targetRevision);
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
     * @throws \RuntimeException
     * @throws GitException
     */
    public function diffFile(string $repoPath, string $targetRevision, string $currentRevision, string $file): string
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
     * @throws \RuntimeException
     * @throws GitException
     */
    public function listFiles(string $repoPath): string
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
     * @throws \RuntimeException
     * @throws GitException
     */
    public function getRemoteBranches(string $repoPath): array
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
            return [];
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
     * @return array
     * @throws \RuntimeException
     * @throws GitException
     */
    public function getLocalBranches(string $repoPath): array
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
            return [];
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
     * @throws \RuntimeException
     * @throws GitException
     */
    public function switchBranch(string $repoPath, string $branch): bool
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
        $this->exec('checkout ' . $branch);
        chdir($oldDir);
        return true;
    }

    /**
     * Removes old already deleted branches from repository.
     *
     * @param string $repoPath
     * @return bool
     * @throws \RuntimeException
     */
    public function pruneRemoteBranches(string $repoPath): bool
    {
        if (empty($repoPath)) {
            throw new \RuntimeException('Required parameter missing.');
        }
        $oldDir = getcwd();
        if (@chdir($repoPath) === false) {
            throw new \RuntimeException('Could not change to repository directory.');
        }
        try {
            $this->exec('remote prune origin');
            chdir($oldDir);
            return true;
        } catch (GitException $e) {
            $this->logger->error('Git prune command failed.' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a remote url is accessible.
     *
     * @param string $repoUrl
     * @return bool
     */
    public function checkRemoteConnectivity(string $repoUrl): bool
    {
        if (empty($repoUrl)) {
            return false;
        }
        $command = 'ls-remote -q -h --exit-code ' . $repoUrl;
        try {
            $this->exec($command);
            return true;
        } catch (GitException $e) {
            $this->logger->error('Remote Repository URL not accessible.');
            return false;
        }
    }

    /**
     * Executes a git command and returns response.
     *
     * @param string $command
     * @param bool $checkForConflicts flag to allow to search for merge conflicts during exec
     * @throws GitException
     * @return string
     */
    protected function exec(string $command): string
    {
        $command = 'GIT_TERMINAL_PROMPT=0 git ' . $command;
        $command = escapeshellcmd($command) . ' 2>&1';
        exec($command, $output, $exitCode);
        $response = implode("\n", $output) ?? '';

        if ($exitCode !== 0) {
            throw new GitException('Git command exited with non zero return code. Git output: ' . $response);
        }

        return $response;
    }

    public function isMergeInProgress(): bool
    {
        $command = 'GIT_TERMINAL_PROMPT=0 git merge HEAD';
        $command = escapeshellcmd($command) . ' 2>&1';

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return true;
        }

        if (count($output) !== 1) {
            return true;
        }

        foreach ($output as $message) {
            if (in_array($message, [
                'error: Merging is not possible because you have unmerged files.',
                'error: Pulling is not possible because you have unmerged files.',
                'error: you need to resolve your current index first',
                'hint: Fix them up in the work tree, and then use \'git add/rm <file>\'',
                'hint: as appropriate to mark resolution and make a commit.',
                'fatal: Exiting because of an unresolved conflict.',
            ])) {
                return true;
            }
        }

        return false;
    }

    public function handleMergeAbort(): bool
    {
        try {
            $this->exec('merge --abort');
            return true;
        } catch (GitException $e) {
            $this->logger->error('Git auto-merge abort failed.' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $newBranchName comes from DB 'deployments.branch' (we are currently in 'deployments.target_branch')
     * @return bool
     */
    public function forcedAutoMerge(string $newBranchName): bool
    {
        try {
            if (empty($newBranchName)) {
                throw new GitException('Branch to merge cannot be empty');
            }

            $this->exec('merge -X theirs ' . $newBranchName);
            return true;
        } catch (GitException $e) {
            $this->logger->error('Git forced merge-command failed.' . $e->getMessage());
            return false;
        }
    }
}
