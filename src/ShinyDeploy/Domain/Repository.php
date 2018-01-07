<?php
namespace ShinyDeploy\Domain;

use RuntimeException;
use ShinyDeploy\Core\Domain;

class Repository extends Domain
{
    /** @var Git $git */
    protected $git;

    public function init(array $data) : void
    {
        $this->data = $data;
        $this->git = new Git($this->config, $this->logger);
    }

    /**
     * Returns repository name.
     *
     * @return string
     */
    public function getName() : string
    {
        if (!empty($this->data['name'])) {
            return $this->data['name'];
        }
        return '';
    }

    /**
     * Checks if repositories local folder exists.
     *
     * @return bool
     */
    public function exists() : bool
    {
        $repoPath = $this->getLocalPath();
        if (!file_exists($repoPath)) {
            return false;
        }
        if (!file_exists($repoPath . '/.git')) {
            return false;
        }
        return true;
    }

    /**
     * Updates a repository by doing a git pull.
     *
     * @return bool
     */
    public function doPull() : bool
    {
        $repoUrl = $this->getCredentialsUrl();
        $repoPath = $this->getLocalPath();
        $response = $this->git->gitPull($repoUrl, $repoPath);
        if (strpos($response, 'up-to-date') !== false ||
            strpos($response, 'done.') !== false ||
            stripos($response, 'Fast-forward') !== false) {
            return true;
        }
        $this->logger->error('Git pull failed. Git response was: ' . $response);
        return false;
    }

    /**
     * Fetches repository from git by doing a git clone.
     *
     * @return bool
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     */
    public function doClone() : bool
    {
        $repoUrl = $this->getCredentialsUrl();
        $repoPath = $this->createLocalPath();
        $response = $this->git->gitClone($repoUrl, $repoPath);
        if (strpos($response, 'done.') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Fetches a list of repository (remote) branches.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getBranches() : array
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Repository data missing. Not initialized?');
        }
        $repoPath = $this->getLocalPath();
        $branches = $this->git->getRemoteBranches($repoPath);
        return $branches;
    }

    /**
     * Switch repository to given branch.
     *
     * @param string $branch
     * @return bool
     */
    public function switchBranch(string $branch) : bool
    {
        $repoPath = $this->getLocalPath();
        $switchResult = $this->git->switchBranch($repoPath, $branch);
        return $switchResult;
    }

    /**
     * Removes old branches from repository.
     *
     * @return bool
     */
    public function doPrune() : bool
    {
        $repoPath = $this->getLocalPath();
        return $this->git->pruneRemoteBranches($repoPath);
    }

    /**
     * Returns repository revision for given branch.
     *
     * @param string $branch
     * @return string
     */
    public function getRevision(string $branch) : string
    {
        $repoPath = $this->getLocalPath();
        $revision = $this->git->getLocalRepositoryRevision($repoPath, $branch);
        return $revision;
    }

    /**
     * Retuns remote repository revision for given branch.
     *
     * @param string $branch
     * @return string
     */
    public function getRemoteRevision(string $branch) : string
    {
        $repoPath = $this->getLocalPath();
        $repoUrl = $this->getCredentialsUrl();
        $revision = $this->git->getRemoteRepositoryRevision($repoPath, $repoUrl, $branch);
        return $revision;
    }

    /**
     * Get list of files in repository.
     *
     * @return array
     */
    public function listFiles() : array
    {
        $files = [];
        $repoPath = $this->getLocalPath();
        $response = $this->git->listFiles($repoPath);
        $lines = explode("\n", $response);
        if (empty($lines)) {
            return $files;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            array_push($files, $line);
        }
        return $files;
    }

    /**
     * Fetches list of changed files between two revisions.
     *
     * @param string $revisionA
     * @param string $revisionB
     * @return array
     */
    public function getDiff(string $revisionA, string $revisionB) : array
    {
        $diff = [];
        $repoPath = $this->getLocalPath();
        $response = $this->git->diff($repoPath, $revisionA, $revisionB);

        $lines = explode("\n", $response);
        if (empty($lines)) {
            return $diff;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $status = $line[0];
            $file = trim(substr($line, 1));
            $item = [
                'file' => $file,
                'type' => $status,
            ];
            array_push($diff, $item);
        }
        return $diff;
    }

    /**
     * Gets a git diff for given file.
     *
     * @param string $file
     * @param string $revisionA
     * @param string $revisionB
     * @return string
     */
    public function getFileDiff(string $file, string $revisionA, string $revisionB) : string
    {
        $repoPath = $this->getLocalPath();
        $diff = $this->git->diffFile($repoPath, $revisionA, $revisionB, $file);
        return $diff;
    }

    /**
     * Gets local repository path.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getLocalPath() : string
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Repository data missing. Not initialized?');
        }
        $repoUrlPath = parse_url($this->data['url'], PHP_URL_PATH);
        $repoFolder = str_replace('.git', '', $repoUrlPath);
        $repoPath = $this->config->get('repositories.path') . $repoFolder;
        return $repoPath;
    }

    /**
     * Creates local repository path.
     *
     * @return string
     */
    protected function createLocalPath() : string
    {
        $localPath = $this->getLocalPath();
        if (file_exists($localPath)) {
            return $localPath;
        }
        if (mkdir($localPath, 0750, true) === false) {
            throw new RuntimeException('Could not create repository path.');
        }
        return $localPath;
    }

    /**
     * Returns repository url after adding login credentials (if available).
     *
     * @return string
     * @throws \RuntimeException
     */
    protected function getCredentialsUrl() : string
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Repository data missing. Not initialized?');
        }

        $credentials = '';
        if (!empty($this->data['username'])) {
            $credentials .= $this->data['username'];
        }
        if (!empty($this->data['password'])) {
            $credentials .= ':' . $this->data['password'];
        }
        $url = str_replace('://', '://' . $credentials . '@', $this->data['url']);
        return $url;
    }

    /**
     * Deletes files of a local repository.
     *
     * @param string $repoPath
     * @return bool
     */
    public function remove(string $repoPath = '') : bool
    {
        if (empty($repoPath)) {
            $repoPath = $this->getLocalPath();
        }
        if (!is_dir($repoPath)) {
            return false;
        }
        $this->removeDir($repoPath);
        return true;
    }

    /**
     * Checks weather git is executable.
     *
     * @return bool
     */
    public function checkGit() : bool
    {
        $versionString = $this->git->getVersion();
        return ($versionString === false) ? false : true;
    }

    /**
     * Checks if URL responses with status 200.
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function checkConnectivity() : bool
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Repository data not set. Missing inititialization?');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->data['url']);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        if (!empty($this->data['username'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->data['username'].':'.$this->data['password']);
        }
        $headers = curl_exec($ch);
        curl_close($ch);
        if (stripos($headers, 'HTTP/1.1 200') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Recursively removes a not empty directory.
     *
     * @param string $path
     * @return void
     */
    private function removeDir(string $path) : void
    {
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_link($path.'/'.$object)) {
                        continue;
                    }
                    if (filetype($path.'/'.$object) === 'dir') {
                        $this->removeDir($path.'/'.$object);
                    } else {
                        unlink($path.'/'.$object);
                    }
                }
            }
            reset($objects);
            rmdir($path);
        }
    }
}
