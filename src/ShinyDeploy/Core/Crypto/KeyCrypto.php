<?php namespace ShinyDeploy\Core\Crypto;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use ShinyDeploy\Exceptions\CryptographyException;

class KeyCrypto
{
    /**
     * Encrypts a string using given key.
     *
     * @param string $string
     * @param string $key
     * @throws CryptographyException
     * @return string
     */
    public function encryptString(string $string, string $key) : string
    {
        try {
            $defuseKey = Key::loadFromAsciiSafeString($key);
            return Crypto::encrypt($string, $defuseKey);
        } catch (CryptoException $ex) {
            throw new CryptographyException('Could not encrypt string.');
        }
    }

    /**
     * Decrypts a string using given key.
     *
     * @param string $string
     * @param string $key
     * @throws CryptographyException
     * @return string
     */
    public function decryptString(string $string, string $key) : string
    {
        try {
            $defuseKey = Key::loadFromAsciiSafeString($key);
            return Crypto::decrypt($string, $defuseKey);
        } catch (CryptoException $ex) {
            throw new CryptographyException('Could not decrypt string.');
        }
    }

    /**
     * Encrypts all fields of an array using given key.
     *
     * @param array $data
     * @param string $key
     * @throws CryptographyException
     * @return array
     */
    public function encryptArray(array $data, string $key) : array
    {
        if (empty($data)) {
            return $data;
        }
        $keys = array_keys($data);
        return $this->encryptArrayParts($data, $keys, $key);
    }

    /**
     * Decrypts all fields of an array using given key.
     *
     * @param array $data
     * @param string $key
     * @throws CryptographyException
     * @return array
     */
    public function decryptArray(array $data, string $key) : array
    {
        if (empty($data)) {
            return $data;
        }
        $keys = array_keys($data);
        return $this->decryptArrayParts($data, $keys, $key);
    }

    /**
     * Encryptes specified fields of an array using given key.
     *
     * @param array $data
     * @param array $keys
     * @param string $key
     * @throws CryptographyException
     * @return array
     */
    public function encryptArrayParts(array $data, array $keys, string $key) : array
    {
        if (empty($data) || empty($keys)) {
            return $data;
        }
        foreach ($keys as $aKey) {
            if (!isset($data[$aKey])) {
                continue;
            }
            if (is_array($data[$aKey]) || is_object($data[$aKey])) {
                throw new CryptographyException('Can not encrypt multi-dimensional array or object');
            }
            $data[$aKey] = $this->encryptString($data[$aKey], $key);
        }
        return $data;
    }

    /**
     * Decrypts specified fields of an array.
     *
     * @param array $data
     * @param array $keys
     * @param string $key
     * @throws CryptographyException
     * @return array
     */
    public function decryptArrayParts(array $data, array $keys, string $key) : array
    {
        if (empty($data) || empty($keys)) {
            return $data;
        }
        foreach ($keys as $aKey) {
            if (!isset($data[$aKey])) {
                continue;
            }
            if (is_array($data[$aKey]) || is_object($data[$aKey])) {
                throw new CryptographyException('Can not decrypt multi-dimensional array or object');
            }
            $data[$aKey] = $this->decryptString($data[$aKey], $key);
        }
        return $data;
    }
}
