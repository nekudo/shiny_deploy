<?php
namespace ShinyDeploy\Domain\Database;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use Exception;
use InvalidArgumentException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Exceptions\CryptographyException;
use ShinyDeploy\Exceptions\DatabaseException;

class Auth extends DatabaseDomain
{
    /**
     * Generates JWT.
     *
     * @param string $username
     * @param string $password
     * @param string $clientId
     * @return string
     */
    public function generateToken(string $username, string $password, string $clientId) : string
    {
        try {
            $signer = new Sha256;
            $builder = new Builder;
            $builder->setIssuer('ShinyDeploy')
                ->setId($clientId, true)
                ->setIssuedAt(time())
                ->setNotBefore(time())
                ->setExpiration(time() + 3600*8)
                ->set('usr', $username)
                ->set('pwd', $this->encryptPassword($password))
                ->sign($signer, $this->config->get('auth.secret'));
            $token = (string)$builder->getToken(); // Retrieves the generated token
            return $token;
        } catch (Exception $e) {
            $this->logger->error(
                'Token Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return '';
        }
    }

    /**
     * Checks if JWT is valid/not manipulated.
     *
     * @param string $token
     * @param string $clientId
     * @return boolean
     */
    public function validateToken(string $token, string $clientId) : bool
    {
        try {
            $parser = new Parser;
            $parsedToken = $parser->parse($token);
            $data = new ValidationData();
            $data->setIssuer('ShinyDeploy');
            $data->setId($clientId);
            $validationResult = $parsedToken->validate($data);
            if ($validationResult !== true) {
                return false;
            }

            $signer = new Sha256;
            $verificationResult = $parsedToken->verify($signer, $this->config->get('auth.secret'));
            return $verificationResult;
        } catch (Exception $e) {
            $this->logger->error(
                'Token Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }

    /**
     * Checks whether a user exists in database.
     *
     * @param string $username
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @return bool
     */
    public function userExists(string $username) : bool
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username can not be empty.');
        }
        $statement = "SELECT `id` FROM users WHERE `username` = %s";
        $userId = (int)$this->db->prepare($statement, $username)->getValue();
        return ($userId > 0);
    }

    /**
     * Fetches password-hash for username from database.
     *
     * @param string $username
     * @return string
     * @throws InvalidArgumentException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     */
    public function getPasswordHashByUsername(string $username) : string
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username can not be empty.');
        }
        $statement = "SELECT `password` FROM users WHERE `username` = %s";
        $passwordHash = $this->db->prepare($statement, $username)->getValue();
        return $passwordHash;
    }

    /**
     * Fetches encryption key from database identified by username.
     *
     * @param string $username
     * @return string
     * @throws InvalidArgumentException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     */
    public function getEncryptionKeyByUsername(string $username) : string
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username can not be empty.');
        }
        $statement = "SELECT `encryption_key` FROM users WHERE `username` = %s";
        $encryptionKey = $this->db->prepare($statement, $username)->getValue();
        return $encryptionKey;
    }

    /**
     * Fetches users encryption key using username and password from JWT.
     *
     * @param string $token
     * @throws InvalidArgumentException
     * @throws CryptographyException
     * @throws DatabaseException
     * @return string
     */
    public function getEncryptionKeyFromToken(string $token) : string
    {
        if (empty($token)) {
            throw new InvalidArgumentException('Token can not be empty');
        }

        $parser = new Parser;
        $parsedToken = $parser->parse($token);
        $username = $parsedToken->getClaim('usr');
        $passwordEncrypted = $parsedToken->getClaim('pwd');
        if (empty($username) || empty($passwordEncrypted)) {
            throw new CryptographyException('Could not get username from token.');
        }
        $encryptionKey = $this->getEncryptionKeyByUsername($username);
        if (empty($encryptionKey)) {
            throw new DatabaseException('Could not get encryption key.');
        }
        $password = $this->decryptPassword($passwordEncrypted);
        if (empty($password)) {
            throw new CryptographyException('Could not decrypt password.');
        }
        $encryptionKeyDecrypted = $this->decryptString($encryptionKey, $password);
        if (empty($encryptionKeyDecrypted)) {
            throw new CryptographyException('Could not decrypt encryption key.');
        }

        return $encryptionKeyDecrypted;
    }

    /**
     * Saves new user to database.
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function createUser(string $username, string $password) : bool
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username can not be emtpy.');
        }
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }
        $passwordHash = hash('sha256', $password);
        $statement = "INSERT INTO users (`username`,`password`) VALUES (%s,%s)";
        return $this->db->prepare($statement, $username, $passwordHash)->execute();
    }

    /**
     * Saves new system-user to database.
     *
     * @param string $password
     * @return bool
     * @throws CryptographyException
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function createSystemUser(string $password) : bool
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }
        $passwordHash = hash('sha256', $password);
        $encryptionKey = $this->generateEncryptionKey($password);
        if (empty($encryptionKey)) {
            return false;
        }
        $statement = "INSERT INTO users (`username`,`password`,`encryption_key`) VALUES (%s,%s,%s)";
        return $this->db->prepare($statement, 'system', $passwordHash, $encryptionKey)->execute();
    }

    /**
     * Checks if an API password is valid.
     *
     * @param string $apiKey
     * @param string $apiPassword
     * @throws DatabaseException
     * @return bool
     */
    public function apiPasswordIsValid(string $apiKey, string $apiPassword) : bool
    {
        if (empty($apiKey) || empty($apiPassword)) {
            return false;
        }
        $passwordHash = hash('sha256', $apiPassword . $this->config->get('auth.secret'));
        $statement = "SELECT api_key FROM api_keys WHERE `password` = %s";
        $apiKeyDb = $this->db->prepare($statement, $passwordHash)->getValue();
        if (empty($apiKeyDb)) {
            return false;
        }
        return ($apiKeyDb === $apiKey);
    }

    /**
     * Generates new encryption key and stores it in database.
     *
     * @param string $password
     * @throws CryptographyException
     * @return string
     */
    protected function generateEncryptionKey(string $password) : string
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }

        // generate key:
        try {
            $key = Key::createNewRandomKey()->saveToAsciiSafeString();
        } catch (CryptoException $ex) {
            throw new CryptographyException('Could not genereate key.');
        }

        // encrypt key:
        return $this->encryptString($key, $password);
    }

    /**
     * Encrypts a password for storage in JWT.
     *
     * @param string $password
     * @return string
     */
    protected function encryptPassword(string $password) : string
    {
        $passwordEncrypted = $this->encryptString($password, $this->config->get('auth.secret'));
        $passwordEncryptedEncoded = base64_encode($passwordEncrypted);
        return $passwordEncryptedEncoded;
    }

    /**
     * Decrypts a password from JWT for usage.
     *
     * @param string $encryptedPassword
     * @throws CryptographyException
     * @return string
     */
    protected function decryptPassword(string $encryptedPassword) : string
    {
        $passwordDecoded = base64_decode($encryptedPassword);
        $passwordDecrypted = $this->decryptString($passwordDecoded, $this->config->get('auth.secret'));
        return $passwordDecrypted;
    }

    /**
     * Encrypts a string using the given password as key.
     *
     * @param string $string
     * @param string $password
     * @return string
     */
    private function encryptString(string $string, string $password) : string
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }
        $encryption = new PasswordCrypto(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $stringEncrypted = $encryption->encrypt($string, $password);
        return $stringEncrypted;
    }

    /**
     * Decrypts a string using given password.
     *
     * @param string $string
     * @param string $password
     * @throws CryptographyException
     * @throws InvalidArgumentException
     * @return string
     */
    private function decryptString(string $string, string $password) : string
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }
        $encryption = new PasswordCrypto(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $stringDecrypted = $encryption->decrypt($string, $password);
        return $stringDecrypted;
    }
}
