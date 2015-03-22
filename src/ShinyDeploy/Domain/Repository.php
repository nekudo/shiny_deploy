<?php
namespace ShinyDeploy\Domain;

use Guzzle\Common\Exception\RuntimeException;
use ShinyDeploy\Core\Domain;

class Repository extends Domain
{
    /**
     * Checks if repositories local folder exists.
     *
     * @param string $idSource
     * @return bool
     */
    public function exists($idSource)
    {
        if (empty($idSource)) {
            throw new \RuntimeException('Source-ID can not be empty.');
        }
        $repoPath = $this->getLocalPath($idSource);
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
     * @param string $idSource
     * @return string
     */
    public function getLocalPath($idSource)
    {
        if (empty($idSource)) {
            throw new \RuntimeException('Source-ID can not be empty.');
        }
        $repoConfig = $this->config->get('sources.'.$idSource, null);
        if (empty($repoConfig)) {
            throw new \RuntimeException('Repository configuration not found.');
        }
        $repoUrlPath = parse_url($repoConfig['url'], PHP_URL_PATH);
        $repoFolder = str_replace('.git', '', $repoUrlPath);
        $repoPath = $this->config->get('repositories.path') . $repoFolder;
        return $repoPath;
    }

    /**
     * Creates local repository path.
     *
     * @param string $idSource
     * @return bool|string
     */
    public function createLocalPath($idSource)
    {
        if (empty($idSource)) {
            throw new \RuntimeException('Source-ID can not be empty.');
        }
        $localPath = $this->getLocalPath($idSource);
        if (file_exists($localPath)) {
            return $localPath;
        }
        if (mkdir($localPath, 0750, true) === false) {
            throw new RuntimeException('Could not create repository path.');
        }
        return $localPath;
    }
}
