<?php namespace ShinyDeploy\Core\Crypto;

use Defuse\Crypto\Crypto;

class KeyCrypto
{
    /**
     * Encryptes a string using given key.
     *
     * @param string $string
     * @param string $key
     * @return string|boolean
     */
    public function encryptString($string, $key)
    {
        try {
            $enryptedString = Crypto::encrypt($string, $key);
        } catch (Ex\CryptoTestFailedException $ex) {
            return false;
        } catch (Ex\CannotPerformOperationException $ex) {
            return false;
        }
        return $enryptedString;
    }

    /**
     * Decryptes a string using given key.
     *
     * @param string $string
     * @param string $key
     * @return string|boolean
     */
    public function decryptString($string, $key)
    {
        try {
            $decryptedString = Crypto::decrypt($string, $key);
        } catch (Ex\InvalidCiphertextException $ex) {
            return false;
        } catch (Ex\CryptoTestFailedException $ex) {
            return false;
        } catch (Ex\CannotPerformOperationException $ex) {
            return false;
        }
        return $decryptedString;
    }

    /**
     * Encryptes all fields of an array using given key.
     *
     * @param array $data
     * @param string $key
     * @return array|bool
     */
    public function encryptArray(array $data, $key)
    {
        if (empty($data)) {
            return false;
        }
        $keys = array_keys($data);
        return $this->encryptArrayParts($data, $keys, $key);
    }

    /**
     * Decryptes all fields of an array using given key.
     *
     * @param array $data
     * @param string $key
     */
    public function decryptArray(array $data, $key)
    {
        if (empty($data)) {
            return false;
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
     * @return array|bool
     */
    public function encryptArrayParts(array $data, array $keys, $key)
    {
        if (empty($data) || empty($keys)) {
            return false;
        }
        foreach ($keys as $aKey) {
            if (!isset($data[$aKey])) {
                return false;
            }
            if (is_array($data[$aKey]) || is_object($data[$aKey])) {
                return false;
            }
            $data[$aKey] = $this->encryptString($data[$aKey], $key);
        }
        return $data;
    }

    /**
     * Decryptes specified fields of an array.
     *
     * @param array $data
     * @param array $keys
     * @param string $key
     * @return array|boolean
     */
    public function decryptArrayParts(array $data, array $keys, $key)
    {
        if (empty($data) || empty($keys)) {
            return false;
        }
        foreach ($keys as $aKey) {
            if (!isset($data[$aKey])) {
                return false;
            }
            if (is_array($data[$aKey]) || is_object($data[$aKey])) {
                return false;
            }
            $data[$aKey] = $this->decryptString($data[$aKey], $key);
        }
        return $data;
    }
}
