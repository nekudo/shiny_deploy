<?php
namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Git;
use ShinyDeploy\Domain\Repositories;
use ShinyDeploy\Domain\Repository;
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
            $repositoriesDomain = new Repositories($this->config, $this->logger);
            $repositoryDomain = new Repository($this->config, $this->logger);
            $gitDomain = new Git($this->config, $this->logger);

            // Get repo data.
            $repositoryData = $repositoriesDomain->getRepositoryData($repositoryId);
            if (empty($repositoryData)) {
                $notificationResponder->send('Could not clone repository. Repository not found in database.', 'danger');
            }

            // Check if repo is reachable:
            if ($repositoriesDomain->checkUrl($repositoryData) === false) {
                $notificationResponder->send('Could not clone repository. URL not reachable.', 'warning');
            }

            // Check if repo already exists:
            $repoPath = $repositoryDomain->createLocalPath($repositoryData['url']);
            if ($repositoryDomain->exists($repoPath) === true) {
                $notificationResponder->send(
                    'Git clone of repository ' . $repositoryData['name'] . ' successfully completed.',
                    'success'
                );
                return true;
            }

            // Clone the repository:
            $repositoryUrl = $repositoriesDomain->getCredentialsUrl($repositoryData);
            $response = $gitDomain->gitClone($repositoryUrl, $repoPath);
            if (strpos($response, 'done.') !== false) {
                $notificationResponder->send(
                    'Git clone of repository ' . $repositoryData['name'] . ' successfully completed.',
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
