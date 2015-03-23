<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Responder\WsGatewayResponder;

class Deploy extends Action
{
    /** @var  WsGatewayResponder $responder */
    protected $responder;

    /** @var  Git $gitDomain */
    protected $gitDomain;

    /** @var  Repository $repositoryDomain */
    protected $repositoryDomain;

    public function __invoke($clientId, $idSource, $idTarget)
    {
        try {
            $gitDomain = new Git($this->config, $this->logger);
            $this->gitDomain = $gitDomain;

            $repositoryDomain = new Repository($this->config, $this->logger);
            $this->repositoryDomain = $repositoryDomain;

            $responder = new WsGatewayResponder($this->config, $this->logger);
            $responder->setClientId($clientId);
            $this->responder = $responder;

            // check if git executable is available:
            if ($this->checkGitExecutable() === false) {
                return false;
            }

            // prepare local repository:
            if ($this->prepareRepository($idSource) === false) {
                return false;
            }

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            $this->responder->log('Exception: ' . $e->getMessage() . ' Aborting.', 'error', 'DeployAction');
            return false;
        }
    }

    /**
     * Check weather git is executable.
     * @return bool
     */
    protected function checkGitExecutable()
    {
        $versionString = $this->gitDomain->getVersion();
        if ($versionString === false) {
            $this->responder->log('Git executable not found. Aborting job.', 'error', 'DeployAction');
            return false;
        }
        $this->responder->log($versionString . ' detected.', 'default', 'DeployAction');
        return true;
    }

    /**
     * If local repository does not exist it will be pulled from git. It it exists it will be updated.
     *
     * @param string $idSource
     * @return bool
     */
    protected function prepareRepository($idSource)
    {
        $repoPath = $this->repositoryDomain->createLocalPath($idSource);
        if ($this->repositoryDomain->exists($idSource) === false) {
            $this->responder->log('Local repository not found. Starting git clone.', 'default', 'DeployAction');
            $response = $this->gitDomain->gitClone($idSource, $repoPath);
            $this->responder->log($response, 'default', 'Git');
            if (strpos($response, 'done.') !== false) {
                $this->responder->log('Repository successfully cloned.', 'success', 'DeployAction');
                return true;
            }
        } else {
            $this->responder->log('Local repository found. Starting update.', 'default', 'DeployAction');
            $response = $this->gitDomain->gitPull($idSource, $repoPath);
            $this->responder->log($response, 'default', 'Git');
            if (strpos($response, 'up-to-date') !== false ||
                (stripos($response, 'updating') !== false && strpos($response, 'done.') !== false)) {
                $this->responder->log('Local repository successfully updated.', 'success', 'DeployAction');
                return true;
            }
        }
        $this->responder->log('Error updating repository. Aborting.', 'error', 'DeployAction');
        return false;
    }
}
