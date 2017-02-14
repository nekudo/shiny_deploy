<?php namespace ShinyDeploy\Worker;

use ShinyDeploy\Action\WsWorkerAction\Deploy;
use ShinyDeploy\Action\WsWorkerAction\GetChangedFiles;
use ShinyDeploy\Action\WsWorkerAction\GetFileDiff;
use ShinyDeploy\Action\WsWorkerAction\SetLocalRevision;
use ShinyDeploy\Action\WsWorkerAction\SetRemoteRevision;
use ShinyDeploy\Action\ApiAction\Deploy as ApiDeploy;
use ShinyDeploy\Core\Worker;
use ShinyDeploy\Exceptions\MissingDataException;

require __DIR__ . '/../../../vendor/autoload.php';

class DeploymentActions extends Worker
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
     * @throws Exception
     * @return bool
     */
    public function deploy(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            $tasksToRun = (!empty($params['tasksToRun'])) ? $params['tasksToRun'] : [];
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            if (empty($params['deploymentId'])) {
                throw new MissingDataException('DeploymentId can not be empty.');
            }
            $action = new Deploy($this->config, $this->logger);
            $action->setClientId($params['clientId']);
            $action->setToken($params['token']);
            $action->__invoke($params['deploymentId'], $tasksToRun);
        } catch (Exception $e) {
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
     * @throws Exception
     * @return bool
     */
    public function apiDeploy(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['apiKey'])) {
                throw new MissingDataException('API key missing.');
            }
            if (empty($params['apiPassword'])) {
                throw new MissingDataException('API password missing.');
            }
            $requestParameters = [];
            if (!empty($params['requestParameters'])) {
                $requestParameters = $params['requestParameters'];
            }
            $action = new ApiDeploy($this->config, $this->logger);
            $action->setApiKey($params['apiKey']);
            $action->setApiPassword($params['apiPassword']);
            $action->__invoke($requestParameters);
        } catch (Exception $e) {
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
     * @throws Exception
     * @return bool
     */
    public function getChangedFiles(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            if (empty($params['deploymentId'])) {
                throw new MissingDataException('DeploymentId can not be empty.');
            }
            $action = new GetChangedFiles($this->config, $this->logger);
            $action->setClientId($params['clientId']);
            $action->setToken($params['token']);
            $action->__invoke($params['deploymentId']);
        } catch (\Exception $e) {
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
     * @throws Exception
     * @return bool
     */
    public function getFileDiff(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            $action = new GetFileDiff($this->config, $this->logger);
            $action->setClientId($params['clientId']);
            $action->setToken($params['token']);
            $action->__invoke($params);
        } catch (\Exception $e) {
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
     * @throws Exception
     * @return bool
     */
    public function setLocalRevision(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            $action = new SetLocalRevision($this->config, $this->logger);
            $action->setClientId($params['clientId']);
            $action->setToken($params['token']);
            $action->__invoke($params);
        } catch (\Exception $e) {
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
     * @throws Exception
     * @return bool
     */
    public function setRemoteRevision(\GearmanJob $Job)
    {
        try {
            $this->countJob();
            $params = json_decode($Job->workload(), true);
            if (empty($params['clientId'])) {
                throw new MissingDataException('ClientId can not be empty.');
            }
            if (empty($params['token'])) {
                throw new MissingDataException('Token can not be empty.');
            }
            $action = new SetRemoteRevision($this->config, $this->logger);
            $action->setClientId($params['clientId']);
            $action->setToken($params['token']);
            $action->__invoke($params);
        } catch (\Exception $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return true;
    }
}
