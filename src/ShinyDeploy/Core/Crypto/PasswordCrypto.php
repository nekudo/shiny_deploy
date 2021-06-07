<?php namespace ShinyDeploy\Core\Crypto;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\CryptoException;
use ShinyDeploy\Exceptions\CryptographyException;

class PasswordCrypto
{
    /**
     * Decrypt the data with the provided password.
     *
     * @param string $data The encrypted data
     * @param string $password The password to use for decryption
     * @return string Decrypted data
     * @throws CryptographyException
     */
    public function decrypt(string $data, string $password): string
    {
        try {
            return Crypto::decryptWithPassword($data, $password);
        } catch (CryptoException $e) {
            throw new CryptographyException($e->getMessage());
        }
    }

    /**
     * Encrypt the supplied data using the supplied key
     *
     * @param string $data The data to encrypt
     * @param string $password Password to use for encryption
     * @return string The encrypted data
     * @throws CryptographyException
     */
    public function encrypt(string $data, string $password): string
    {
        try {
            return Crypto::encryptWithPassword($data, $password);
        } catch (CryptoException $e) {
            throw new CryptographyException($e->getMessage());
        }
    }
}
