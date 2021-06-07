<?php namespace ShinyDeploy\Domain\Database;

use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Core\Helper\StringHelper;
use ShinyDeploy\Exceptions\DatabaseException;
use ShinyDeploy\Exceptions\MissingDataException;
use ShinyDeploy\Traits\CryptableDomain;

class ApiKeys extends DatabaseDomain
{
    use CryptableDomain;

    /**
     * Generates new API key and stores it to database.
     *
     * @param int $deploymentId
     * @throws DatabaseException
     * @throws MissingDataException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @return array
     */
    public function addApiKey(int $deploymentId): array
    {
        if (empty($this->encryptionKey)) {
            throw new MissingDataException('Encryption key not set.');
        }
        if (empty($deploymentId)) {
            throw new MissingDataException('Deployment id can not be empty.');
        }

        $apiKey = StringHelper::getRandomString(20);
        $passwordForUrl = StringHelper::getRandomString(16);
        $password = $passwordForUrl . $this->config->get('auth.secret');
        $passwordHash = hash('sha256', $password);
        $encryption = new PasswordCrypto();
        $encryptionKeySave = $encryption->encrypt($this->encryptionKey, $password);
        $statement = "INSERT INTO api_keys (`api_key`,`deployment_id`,`password`,`encryption_key`)"
            . " VALUES (%s,%i,%s,%s)";
        $result = $this->db->prepare($statement, $apiKey, $deploymentId, $passwordHash, $encryptionKeySave)->execute();
        if ($result === false) {
            throw new DatabaseException('Could not store API key to database.');
        }
        return [
            'apiKey' => $apiKey,
            'apiPassword' => $passwordForUrl
        ];
    }

    /**
     * Deletes all existing API keys for specified deployment.
     *
     * @param int $deploymentId
     * @throws MissingDataException
     * @return bool
     */
    public function deleteApiKeysByDeploymentId(int $deploymentId): bool
    {
        if (empty($deploymentId)) {
            throw new MissingDataException('Deployment id can not be empty.');
        }
        try {
            $statement = "DELETE FROM api_keys WHERE `deployment_id` = %i";
            return $this->db->prepare($statement, $deploymentId)->execute();
        } catch (DatabaseException $e) {
            return false;
        }
    }

    /**
     * Fetches API key data by api-key.
     *
     * @param string $apiKey
     * @return array
     * @throws MissingDataException
     * @throws DatabaseException
     */
    public function getDataByApiKey(string $apiKey): array
    {
        if (empty($apiKey)) {
            throw new MissingDataException('API key can not be empty.');
        }
        $statement = "SELECT * FROM api_keys WHERE `api_key` = %s";
        return $this->db->prepare($statement, $apiKey)->getResult();
    }
}
