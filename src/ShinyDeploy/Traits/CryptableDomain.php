<?php namespace ShinyDeploy\Traits;

use ShinyDeploy\Core\Crypto\KeyCrypto;

trait CryptableDomain
{
    /** @var string $encryptionKey */
    protected $encryptionKey;

    /**
     * Sets encryption key.
     *
     * @param string $encryptionKey
     */
    public function setEnryptionKey($encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * Encrypts array (or part of array) before storing to database.
     *
     * @param array $data
     * @param array $fields
     * @return array|bool
     * @throws \RuntimeException
     */
    public function encryptData(array $data, array $fields = [])
    {
        if (empty($this->encryptionKey)) {
            throw new \RuntimeException('Encryption key not set.');
        }
        $keyKrypto = new KeyCrypto;
        if (!empty($fields)) {
            return $keyKrypto->encryptArrayParts($data, $fields, $this->encryptionKey);
        }
        return $keyKrypto->encryptArray($data, $this->encryptionKey);
    }

    /**
     * Decryptes data (or part of it) after fetching it from database.
     *
     * @param array $data
     * @param array $fields
     * @return array|bool
     * @throws \RuntimeException
     */
    public function decryptData(array $data, array $fields = [])
    {
        if (empty($this->encryptionKey)) {
            throw new \RuntimeException('Encryption key not set.');
        }
        $keyKrypto = new KeyCrypto;
        if (!empty($fields)) {
            return $keyKrypto->decryptArrayParts($data, $fields, $this->encryptionKey);
        }
        return $keyKrypto->decryptArray($data, $this->encryptionKey);
    }
}
