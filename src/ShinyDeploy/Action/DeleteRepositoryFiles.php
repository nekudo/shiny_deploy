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
            $repository = new Repository($this->config, $this->logger);

            // Delete files:
            $response = $repository->remove($repoPath);
            if ($response === true) {
                $notificationResponder->send('Local repository files successfully deleted.', 'success');
                return true;
            }

            // Someting went wrong, respond with error message:
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
