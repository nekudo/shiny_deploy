<?php
namespace ShinyDeploy\Action\WsDataAction;

use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Domain\Database\Repositories;
use ShinyDeploy\Exceptions\InvalidPayloadException;
use Valitron\Validator;

class UpdateRepository extends WsDataAction
{
    /**
     * Updates repository data in database.
     *
     * @param array $actionPayload
     * @return bool
     * @throws InvalidPayloadException
     * @throws \ShinyDeploy\Exceptions\AuthException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\InvalidTokenException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     * @throws \ShinyDeploy\Exceptions\WebsocketException
     */
    public function __invoke(array $actionPayload) : bool
    {
        $this->authorize($this->clientId);

        if (!isset($actionPayload['repositoryData'])) {
            throw new InvalidPayloadException('Invalid updateRepository request received.');
        }
        $repositoryData = $actionPayload['repositoryData'];
        $repositories = new Repositories($this->config, $this->logger);

        // validate input:
        $validator = new Validator($repositoryData);
        $validator->rules($repositories->getUpdateRules());
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

        // get users encryption key:
        $auth = new Auth($this->config, $this->logger);
        $encryptionKey = $auth->getEncryptionKeyFromToken($this->token);
        if (empty($encryptionKey)) {
            $this->responder->setError('Could not get encryption key.');
            return false;
        }

        // update repository:
        $repositories->setEnryptionKey($encryptionKey);
        $addResult = $repositories->updateRepository($repositoryData);
        if ($addResult === false) {
            $this->responder->setError('Could not update repository.');
            return false;
        }
        return true;
    }

    /**
     * Checks if url is reachable.
     *
     * @param string $url
     * @param string $username
     * @param string $password
     * @return bool
     */
    private function checkUrl(string $url, string $username = '', string $password = '') : bool
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
