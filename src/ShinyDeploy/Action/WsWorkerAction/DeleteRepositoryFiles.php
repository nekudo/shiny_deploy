<?php namespace ShinyDeploy\Action\WsWorkerAction;

use ShinyDeploy\Domain\Repository;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\WsNotificationResponder;

class DeleteRepositoryFiles extends WsWorkerAction
{
    /**
     * Removes local repository files.
     *
     * @param string $repoPath
     * @return boolean
     * @throws MissingDataException
     */
    public function __invoke($repoPath)
    {
        if (empty($repoPath)) {
            throw new MissingDataException('Repository path can not be empty');
        }

        $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
        $notificationResponder->setClientId($this->clientId);
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
    }
}
