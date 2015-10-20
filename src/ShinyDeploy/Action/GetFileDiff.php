<?php
namespace ShinyDeploy\Action;

use RuntimeException;
use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Responder\WsFileDiffResponder;

class GetFileDiff extends Action
{
    public function __invoke($params)
    {
        try {
            if (empty($params['file']) || empty($params['remoteRevision']) || empty($params['repositoryId'])) {
                throw new RuntimeException('Required parameter missing.');
            }

            $repositories = new Repositories($this->config, $this->logger);
            $repository = $repositories->getRepository($params['repositoryId']);
            $diff = $repository->getFileDiff($params['file'], $params['localRevision'], $params['remoteRevision']);
            $responder = new WsFileDiffResponder($this->config, $this->logger);
            $responder->setClientId($params['clientId']);
            $responder->respond($diff);
            return true;

        } catch (RuntimeException $e) {
            $this->logger->alert(
                'Runtime Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }
}
