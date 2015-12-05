<?php namespace ShinyDeploy\Worker;

require __DIR__ . '/../../../vendor/autoload.php';

use ShinyDeploy\Action\Deploy;
use ShinyDeploy\Action\GetChangedFiles;
use ShinyDeploy\Action\GetFileDiff;
use ShinyDeploy\Action\SetLocalRevision;
use ShinyDeploy\Action\SetRemoteRevision;
use ShinyDeploy\Action\ApiAction\Deploy as ApiDeploy;
use ShinyDeploy\Core\Worker;
use ShinyDeploy\Exceptions\WebsocketException;
use ShinyDeploy\Exceptions\WorkerException;

class Deployer extends Worker
{
    /**
     * Calls all "init methods" and waits for jobs from gearman server.
     */
    protected function registerCallbacks()
    {
        $this->GearmanWorker->addFunction('deploy', [$this, 'deploy']);
        $this->GearmanWorker->addFunction('apiDeploy', [$this, 'apiDeploy']);
        $this->GearmanWorker->addFunction('getChangedFiles', [$this, 'getChangedFiles']);
        $this->GearmanWorker->addFunction('getFileDiff', [$this, 'getFileDiff']);
        $this->GearmanWorker->addFunction('setLocalRevision', [$this, 'setLocalRevision']);
        $this->GearmanWorker->addFunction('setRemoteRevision', [$this, 'setRemoteRevision']);
    }

    /**
     * Handles deployment related actions.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function deploy(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['deploymentId'])) {
                throw new WebsocketException('Can not handle job. No deployment-id provided.');
            }
            if (empty($params['token'])) {
                throw new WebsocketException('Can not handle job. Token not provided.');
            }
            $action = new Deploy($this->config, $this->logger);
            $action->setToken($params['token']);
            $action->__invoke($params['deploymentId'], $params['clientId']);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }

    /**
     * Handles deployment requested by REST API.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function apiDeploy(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['apiKey'])) {
                throw new \InvalidArgumentException('API key missing.');
            }
            if (empty($params['apiPassword'])) {
                throw new \InvalidArgumentException('API password missing.');
            }
            $action = new ApiDeploy($this->config, $this->logger);
            $action->setApiKey($params['apiKey']);
            $action->setApiPassword($params['apiPassword']);
            $action->__invoke();
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }

    /**
     * Handles deployment related actions.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function getChangedFiles(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['deploymentId'])) {
                throw new WebsocketException('Can not handle job. No deployment-id provided.');
            }
            if (empty($params['token'])) {
                throw new WebsocketException('Can not handle job. Token not provided.');
            }
            $action = new GetChangedFiles($this->config, $this->logger);
            $action->setToken($params['token']);
            $action->__invoke($params['deploymentId'], $params['clientId']);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }

    /**
     * Generates a git-diff for given file.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function getFileDiff(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['token'])) {
                throw new WebsocketException('Can not handle job. Token not provided.');
            }

            $action = new GetFileDiff($this->config, $this->logger);
            $action->setToken($params['token']);
            $action->__invoke($params);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }

    /**
     * Fetches local revision of a repository sends it to WSS.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function setLocalRevision(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['token'])) {
                throw new WebsocketException('Can not handle job. Token not provided.');
            }

            $action = new SetLocalRevision($this->config, $this->logger);
            $action->setToken($params['token']);
            $action->__invoke($params);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }

    /**
     * Fetches remote revision of a repository sends it to WSS.
     *
     * @param \GearmanJob $Job
     * @throws \Exception
     * @return bool
     */
    public function setRemoteRevision(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new WebsocketException('Can not handle job. No client-id provided.');
            }
            if (empty($params['token'])) {
                throw new WebsocketException('Can not handle job. Token not provided.');
            }

            $action = new SetRemoteRevision($this->config, $this->logger);
            $action->setToken($params['token']);
            $action->__invoke($params);
        } catch (WorkerException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}
