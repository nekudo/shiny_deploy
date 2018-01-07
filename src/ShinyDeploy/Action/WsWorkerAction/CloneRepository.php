<?php namespace ShinyDeploy\Action\WsWorkerAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\WsNotificationResponder;

class CloneRepository extends WsWorkerAction
{
    /**
     * Clones a repository to local server.
     *
     * @param int $id
     * @return bool
     * @throws MissingDataException
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ZMQException
     */
    public function __invoke(int $id) : bool
    {

        $repositoryId = (int) $id;
        if (empty($repositoryId)) {
            throw new MissingDataException('RepositoryId can not be empty');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            throw new RuntimeException('Could not get encryption key.');
        }

        // Init responder:
        $notificationResponder = new WsNotificationResponder($this->config, $this->logger);
        $notificationResponder->setClientId($this->clientId);

        // Init domains:
        $repositories = new Repositories($this->config, $this->logger);
        $repositories->setEnryptionKey($encryptionKey);
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

        // Something went wrong, send error notice to client:
        $notificationResponder->send('Could not clone repository to local server.', 'danger');
        return false;
    }
}
