<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Responder\WsNotificationResponder;

class CloneRepository extends Action
{
    public function __invoke($repositoryId, $clientId)
    {
        try {
            $repositoryId = (int)$repositoryId;
            if (empty($repositoryId)) {
                throw new \RuntimeException('Repository-ID can not be empty');
            }
            if (empty($clientId)) {
                throw new \RuntimeException('Client-ID can not be empty.');
            }

            // Init responder:
            $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
            $notificationResponder->setClientId($clientId);

            // Init domains:
            $repositories = new Repositories($this->config, $this->logger);
            $repository = $repositories->getRepository($repositoryId);
            if (empty($repository)) {
                $notificationResponder->send('Repository not found in database.', 'danger');
                return false;
            }

            // Check if repo is reachable:
            if ($repository->checkConnectivity() === false) {
                $notificationResponder->send('Could not clone repository. URL not reachable.', 'warning');
            }

            // Check if repo already exists:
            if ($repository->exists() === true) {
                $notificationResponder->send(
                    'Git clone of repository ' . $repository->getName() . ' successfully completed.',
                    'success'
                );
                return true;
            }

            // Clone the repository:
            if ($repository->doClone() === true) {
                $notificationResponder->send(
                    'Git clone of repository ' . $repository->getName() . ' successfully completed.',
                    'success'
                );
                return true;
            }

            // Someting went wrong, send error notice to client:
            $notificationResponder->send('Could not clone repository to local server.', 'danger');
            return false;

        } catch (\RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }
}
