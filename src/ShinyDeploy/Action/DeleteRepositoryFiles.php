<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Responder\WsNotificationResponder;

class DeleteRepositoryFiles extends Action
{
    public function __invoke($repoPath, $clientId)
    {
        try {
            if (empty($repoPath)) {
                throw new \RuntimeException('Repository path can not be empty');
            }
            if (empty($clientId)) {
                throw new \RuntimeException('Client-ID can not be empty.');
            }
            $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
            $notificationResponder->setClientId($clientId);

            $repositoryDomain = new Repository($this->config, $this->logger);
            if ($repositoryDomain->exists($repoPath) === false) {
                $notificationResponder->send(
                    'Could not delete repository files. Local path not existing.',
                    'warning'
                );
                return true;
            } else {
                $response = $repositoryDomain->remove($repoPath);
                if ($response === true) {
                    $notificationResponder->send('Local repository files successfully deleted.', 'success');
                    return true;
                }
            }
            $notificationResponder->send('Could not delete local repository files.', 'danger');
            return false;

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        }
    }
}
