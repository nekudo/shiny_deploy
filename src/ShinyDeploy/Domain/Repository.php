<?php
namespace ShinyDeploy\Domain;

use Guzzle\Common\Exception\RuntimeException;
use ShinyDeploy\Core\Domain;

class Repository extends Domain
{
    /**
     * Checks if repositories local folder exists.
     *
     * @param string $repoPath
     * @return bool
     */
    public function exists($repoPath)
    {
        if (empty($repoPath)) {
            throw new \RuntimeException('Required argument missing.');
        }
        if (!file_exists($repoPath)) {
            return false;
        }
        if (!file_exists($repoPath . '/.git')) {
            return false;
        }
        return true;
    }

    /**
     * Gets local repository path.
     *
     * @param string $repoUrl
     * @return string
     */
    public function getLocalPath($repoUrl)
    {
        if (empty($repoUrl)) {
            throw new \RuntimeException('Required argument missing.');
        }
        $repoUrlPath = parse_url($repoUrl, PHP_URL_PATH);
        $repoFolder = str_replace('.git', '', $repoUrlPath);
        $repoPath = $this->config->get('repositories.path') . $repoFolder;
        return $repoPath;
    }

    /**
     * Creates local repository path.
     *
     * @param string $repoUrl
     * @return bool|string
     */
    public function createLocalPath($repoUrl)
    {
        if (empty($repoUrl)) {
            throw new \RuntimeException('Required argument missing.');
        }
        $localPath = $this->getLocalPath($repoUrl);
        if (file_exists($localPath)) {
            return $localPath;
        }
        if (mkdir($localPath, 0750, true) === false) {
            throw new RuntimeException('Could not create repository path.');
        }
        return $localPath;
    }
}
