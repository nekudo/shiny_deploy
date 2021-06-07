<?php namespace ShinyDeploy\Core\Crypto;

use ShinyDeploy\Exceptions\CryptographyException;

/**
 * A class to handle secure encryption and decryption of arbitrary data
 *
 * @see http://stackoverflow.com/questions/5089841/two-way-encryption-i-need-to-store-passwords-that-can-be-retrieved/5093422#5093422
 *
 * Note that this is not just straight encryption.  It also has a few other
 *  features in it to make the encrypted data far more secure.  Note that any
 *  other implementations used to decrypt data will have to do the same exact
 *  operations.
 *
 * Security Benefits:
 *
 * - Uses Key stretching
 * - Hides the Initialization Vector
 * - Does HMAC verification of source data
 *
 */
class McryptCrypto
{

    /**
     * @var string $cipher The mcrypt cipher to use for this instance
     */
    protected $cipher = '';

    /**
     * @var int $mode The mcrypt cipher mode to use
     */
    protected $mode = '';

    /**
     * @var int $rounds The number of rounds to feed into PBKDF2 for key generation
     */
    protected $rounds = 100;

    /**
     *
     * @param string $cipher The MCRYPT_* cypher to use for this instance
     * @param string $mode The MCRYPT_MODE_* mode to use for this instance
     * @param int $rounds The number of PBKDF2 rounds to do on the key
     */
    public function __construct(string $cipher, string $mode, int $rounds = 100)
    {
        $this->cipher = $cipher;
        $this->mode = $mode;
        $this->rounds = (int) $rounds;
    }

    /**
     * Decrypt the data with the provided key
     *
     * @param string $data The encrypted datat to decrypt
     * @param string $key The key to use for decryption
     * @throws CryptographyException
     * @return string The returned string if decryption is successful
     * @deprecated Use class PasswordCrypto instead.
     */
    public function decrypt(string $data, string $key): string
    {
        $salt = substr($data, 0, 128);
        $enc = substr($data, 128, -64);
        $mac = substr($data, -64);
        list($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);
        if (!$this->hashEquals(hash_hmac('sha512', $enc, $macKey, true), $mac)) {
            throw new CryptographyException('Could not decrypt string. Hashes do not match.');
        }
        $dec = mcrypt_decrypt($this->cipher, $cipherKey, $enc, $this->mode, $iv);
        $data = $this->unpad($dec);
        return $data;
    }

    /**
     * Encrypt the supplied data using the supplied key
     *
     * @param string $data The data to encrypt
     * @param string $key The key to encrypt with
     * @return string The encrypted data
     * @deprecated Use class PasswordCrypto instead.
     */
    public function encrypt(string $data, string $key): string
    {
        $salt = mcrypt_create_iv(128, MCRYPT_DEV_URANDOM);
        list ($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);
        $data = $this->pad($data);
        $enc = mcrypt_encrypt($this->cipher, $cipherKey, $data, $this->mode, $iv);
        $mac = hash_hmac('sha512', $enc, $macKey, true);
        return $salt . $enc . $mac;
    }

    /**
     * Generates a set of keys given a random salt and a master key
     *
     * @param string $salt A random string to change the keys each encryption
     * @param string $key The supplied key to encrypt with
     * @return array An array of keys (a cipher key, a mac key, and a IV)
     */
    protected function getKeys(string $salt, string $key): array
    {
        $ivSize = mcrypt_get_iv_size($this->cipher, $this->mode);
        $keySize = mcrypt_get_key_size($this->cipher, $this->mode);
        $length = 2 * $keySize + $ivSize;
        $key = $this->pbkdf2('sha512', $key, $salt, $this->rounds, $length);
        $cipherKey = substr($key, 0, $keySize);
        $macKey = substr($key, $keySize, $keySize);
        $iv = substr($key, 2 * $keySize);
        return [$cipherKey, $macKey, $iv];
    }

    /**
     * Stretch the key using the PBKDF2 algorithm
     *
     * @see http://en.wikipedia.org/wiki/PBKDF2
     *
     * @param string $algo The algorithm to use
     * @param string $key The key to stretch
     * @param string $salt A random salt
     * @param int $rounds The number of rounds to derive
     * @param int $length The length of the output key
     * @return string The derived key.
     */
    protected function pbkdf2(string $algo, string $key, string $salt, int $rounds, int $length): string
    {
        $size = strlen(hash($algo, '', true));
        $len = ceil($length / $size);
        $result = '';
        for ($i = 1; $i <= $len; $i++) {
            $tmp = hash_hmac($algo, $salt . pack('N', $i), $key, true);
            $res = $tmp;
            for ($j = 1; $j < $rounds; $j++) {
                $tmp  = hash_hmac($algo, $tmp, $key, true);
                $res ^= $tmp;
            }
            $result .= $res;
        }
        return substr($result, 0, $length);
    }

    /**
     * Pads a string to required size.
     *
     * @param string $data
     * @return string
     */
    protected function pad(string $data): string
    {
        $length = mcrypt_get_block_size($this->cipher, $this->mode);
        $padAmount = $length - strlen($data) % $length;
        if ($padAmount == 0) {
            $padAmount = $length;
        }
        return $data . str_repeat(chr($padAmount), $padAmount);
    }

    /**
     * Removes "padding" from a string.
     *
     * @param string $data
     * @throws CryptographyException
     * @return string
     */
    protected function unpad(string $data): string
    {
        $length = mcrypt_get_block_size($this->cipher, $this->mode);
        $last = ord($data[strlen($data) - 1]);
        if ($last > $length) {
            throw new CryptographyException('Could not unpad string.');
        }
        if (substr($data, -1 * $last) !== str_repeat(chr($last), $last)) {
            throw new CryptographyException('Could not unpad string.');
        }
        return substr($data, 0, -1 * $last);
    }

    /**
     * Checks if two hashes match.
     *
     * @param string $a
     * @param string $b
     * @return bool
     */
    protected function hashEquals(string $a, string $b): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        } else {
            $key = mcrypt_create_iv(128, MCRYPT_DEV_URANDOM);
            return hash_hmac('sha512', $a, $key) === hash_hmac('sha512', $b, $key);
        }
    }
}
