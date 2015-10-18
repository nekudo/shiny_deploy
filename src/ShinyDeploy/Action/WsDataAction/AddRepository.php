<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\WebsocketException;
use Valitron\Validator;

class AddRepository extends WsDataAction
{
    public function __invoke($actionPayload)
    {
        if (!isset($actionPayload['repositoryData'])) {
            throw new WebsocketException('Invalid addRepository request received.');
        }
        $repositoryData = $actionPayload['repositoryData'];
        $repositories = new Repositories($this->config, $this->logger);

        // validate input:
        $validator = new Validator($repositoryData);
        $validator->rules($repositories->getCreateRules());
        if (!$validator->validate()) {
            $this->responder->setError('Input validation failed. Please check your data.');
            return false;
        }

        // check if url is okay:
        if (!isset($repositoryData['username'])) {
            $repositoryData['username'] = '';
        }
        if (!isset($repositoryData['password'])) {
            $repositoryData['password'] = '';
        }
        $urlCheckResult = $this->checkUrl(
            $repositoryData['url'],
            $repositoryData['username'],
            $repositoryData['password']
        );
        if ($urlCheckResult === false) {
            $this->responder->setError('Repository check failed. Please check URL, username and password.');
            return false;
        }

        // add repository:
        $repositoryId = $repositories->addRepository($repositoryData);
        if ($repositoryId === false) {
            $this->responder->setError('Could not add repository to database.');
            return false;
        }

        // trigger initial cloning:
        $client = new \GearmanClient;
        $client->addServer($this->config->get('gearman.host'), $this->config->get('gearman.port'));
        $actionPayload['clientId'] = $this->clientId;
        $actionPayload['repositoryId'] = $repositoryId;
        $payload = json_encode($actionPayload);
        $client->doBackground('cloneRepository', $payload);
        return true;
    }

    /**
     * Checks if url is reachable.
     *
     * @param string $url
     * @return boolean
     */
    private function checkUrl($url, $username = '', $password = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        if (!empty($username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
        }
        $headers = curl_exec($ch);
        curl_close($ch);
        if (stripos($headers, 'HTTP/1.1 200') !== false) {
            return true;
        }
        return false;
    }
}
