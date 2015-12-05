<?php namespace ShinyDeploy\Domain\Database;

use InvalidArgumentException;
use RuntimeException;
use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Core\Helper\StringHelper;
use ShinyDeploy\Traits\CryptableDomain;

class ApiKeys extends DatabaseDomain
{
    use CryptableDomain;

    /**
     * Generates new API key and stores it to database.
     *
     * @param int $deploymentId
     * @return array
     */
    public function addApiKey($deploymentId)
    {
        if (empty($this->encryptionKey)) {
            throw new RuntimeException('Encryption key not set.');
        }
        if (empty($deploymentId)) {
            throw new InvalidArgumentException('Deployment id can not be empty.');
        }

        $apiKey = StringHelper::getRandomString(20);
        $password = StringHelper::getRandomString(16);
        $passwordHash = hash('sha256', $password);
        $encryption = new PasswordCrypto(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $encryptionKeySave = $encryption->encrypt($this->encryptionKey, $password);

        $statement = "INSERT INTO api_keys (`api_key`,`deployment_id`,`password`,`encryption_key`)"
            . " VALUES (%s,%d,%s,%s)";
        $res = $this->db->prepare($statement, $apiKey, $deploymentId, $passwordHash, $encryptionKeySave)->execute();
        if ($res === false) {
            throw new \RuntimeException('Could not store API key.');
        }
        return [
            'apiKey' => $apiKey,
            'password' => $password
        ];
    }

    /**
     * Deletes all existing API keys for specified deployment.
     *
     * @param int $deploymentId
     * @return bool
     */
    public function deleteApiKeysByDeploymentId($deploymentId)
    {
        if (empty($deploymentId)) {
            throw new \InvalidArgumentException('Deployment id can not be empty.');
        }
        $statement = "DELETE FROM api_keys WHERE `deployment_id` = %d";
        return $this->db->prepare($statement, $deploymentId)->execute();
    }

    /**
     * Fetches API key data by api-key.
     *
     * @param string $apiKey
     * @return array
     * @throws InvalidArgumentException
     */
    public function getDataByApiKey($apiKey)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key can not be empty.');
        }
        $statement = "SELECT * FROM api_keys WHERE `api_key` = %s";
        $row = $this->db->prepare($statement, $apiKey)->getResult();
        return $row;
    }
}
