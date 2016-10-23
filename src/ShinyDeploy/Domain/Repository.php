<?php
namespace ShinyDeploy\Domain;

use Guzzle\Common\Exception\RuntimeException;
use ShinyDeploy\Core\Domain;

class Repository extends Domain
{
    /** @var Git $git */
    protected $git;

    public function init(array $data)
    {
        $this->data = $data;
        $this->git = new Git($this->config, $this->logger);
    }

    /**
     * Returns repository name.
     *
     * @return string|bool
     */
    public function getName()
    {
        if (!empty($this->data['name'])) {
            return $this->data['name'];
        }
        return false;
    }

    /**
     * Checks if repositories local folder exists.
     *
     * @return bool
     */
    public function exists()
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
     * @return boolean
     */
    public function doPull()
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
     * @return boolean
     */
    public function doClone()
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
     * @return bool|array
     * @throws \RuntimeException
     */
    public function getBranches()
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
    public function switchBranch($branch)
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
    public function doPrune()
    {
        $repoPath = $this->getLocalPath();
        return $this->git->pruneRemoteBranches($repoPath);
    }

    /**
     * Retuns repository revision for given branch.
     *
     * @param string $branch
     * @return string|bool
     */
    public function getRevision($branch)
    {
        $repoPath = $this->getLocalPath();
        $revision = $this->git->getLocalRepositoryRevision($repoPath, $branch);
        return $revision;
    }

    /**
     * Retuns remote repository revision for given branch.
     *
     * @param string $branch
     * @return string|bool
     */
    public function getRemoteRevision($branch)
    {
        $repoPath = $this->getLocalPath();
        $repoUrl = $this->getCredentialsUrl();
        $revision = $this->git->getRemoteRepositoryRevision($repoPath, $repoUrl, $branch);
        return $revision;
    }

    /**
     * Get list of files in repositroy.
     *
     * @return array
     */
    public function listFiles()
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
    public function getDiff($revisionA, $revisionB)
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
    public function getFileDiff($file, $revisionA, $revisionB)
    {
        $repoPath = $this->getLocalPath();
        $diff = $this->git->diffFile($repoPath, $revisionA, $revisionB, $file);
        return $diff;
    }

    /**
     * Gets local repository path.
     *
     * @return string
     */
    public function getLocalPath()
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
    protected function createLocalPath()
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
     */
    protected function getCredentialsUrl()
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
    public function remove($repoPath = '')
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
    public function checkGit()
    {
        $versionString = $this->git->getVersion();
        return ($versionString === false) ? false : true;
    }

    /**
     * Checks if URL responses with status 200.
     *
     * @return bool
     */
    public function checkConnectivity()
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
     */
    private function removeDir($path)
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
