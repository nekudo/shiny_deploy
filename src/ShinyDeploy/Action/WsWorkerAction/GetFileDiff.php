<?php namespace ShinyDeploy\Action\WsWorkerAction;

use RuntimeException;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Responder\WsFileDiffResponder;

class GetFileDiff extends WsWorkerAction
{
    /**
     * Generates a file-diff between two given revisions.
     *
     * @param array $params
     * @return boolean
     * @throws MissingDataException
     * @throws RuntimeException
     */
    public function __invoke(array $params)
    {
        if (empty($params['file'])) {
            throw new MissingDataException('File can not be empty.');
        }
        if (empty($params['remoteRevision'])) {
            throw new MissingDataException('RemoteRevision can not be empty.');
        }
        if (empty($params['repositoryId'])) {
            throw new MissingDataException('RepositoryId can not be empty.');
        }
        if (preg_match('#[0-9a-f]{40}#', $params['remoteRevision']) !== 1) {
            throw new RuntimeException('Invalid remote revision');
        }
        if (preg_match('#[0-9a-f]{40}#', $params['localRevision']) !== 1) {
            throw new RuntimeException('Invalid local revision');
        }

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        $repositories = new Repositories($this->config, $this->logger);
        $repositories->setEnryptionKey($encryptionKey);
        $repository = $repositories->getRepository($params['repositoryId']);
        $diff = $repository->getFileDiff($params['file'], $params['localRevision'], $params['remoteRevision']);
        $responder = new WsFileDiffResponder($this->config, $this->logger);
        $responder->setClientId($this->clientId);
        $responder->respond($diff);
        return true;
    }
}
