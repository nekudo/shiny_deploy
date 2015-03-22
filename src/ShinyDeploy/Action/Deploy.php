<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Responder\WsGatewayResponder;

class Deploy extends Action
{
    /** @var  WsGatewayResponder $responder */
    protected $responder;

    /** @var  Git $gitDomain */
    protected $gitDomain;

    public function __invoke($clientId, $idSource, $idTarget)
    {
        try {
            $gitDomain = new Git($this->config, $this->logger);
            $gitDomain->setClientId($clientId);
            $this->gitDomain = $gitDomain;

            $responder = new WsGatewayResponder($this->config, $this->logger);
            $responder->setClientId($clientId);
            $this->responder = $responder;

            if ($this->checkGitExecutable() === false) {
                return false;
            }
        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
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
}
