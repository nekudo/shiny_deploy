<?php
namespace ShinyDeploy\Domain\Database;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key as SignerKey;
use Lcobucci\JWT\ValidationData;
use ShinyDeploy\Core\Crypto\KeyCrypto;
use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Exceptions\AuthException;
use ShinyDeploy\Exceptions\CryptographyException;
use ShinyDeploy\Exceptions\DatabaseException;
use ShinyDeploy\Exceptions\MissingDataException;

class Auth extends DatabaseDomain
{
    /**
     * Generates JWT.
     *
     * @param string $username
     * @param string $userEncryptionKey
     * @param string $clientId
     * @return string
     */
    public function generateToken(string $username, string $userEncryptionKey, string $clientId) : string
    {
        try {
            $signer = new Sha256;
            $key = new SignerKey($this->config->get('auth.secret'));
            $builder = new Builder;
            $token = (string) $builder->issuedBy('ShinyDeploy')
                ->identifiedBy($clientId, true)
                ->issuedAt(time())
                ->canOnlyBeUsedAfter(time())
                ->expiresAt(time() + 3600*8)
                ->withClaim('usr', $username)
                ->withClaim('uek', $userEncryptionKey)
                ->getToken($signer, $key);

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
     * @throws MissingDataException
     * @return bool
     */
    public function userExists(string $username) : bool
    {
        if (empty($username)) {
            throw new MissingDataException('Username can not be empty.');
        }
        try {
            $statement = "SELECT `id` FROM users WHERE `username` = %s";
            $userId = (int)$this->db->prepare($statement, $username)->getValue();
            return ($userId > 0);
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }

    /**
     * Fetches password-hash for username from database.
     *
     * @param string $username
     * @return string
     * @throws MissingDataException
     */
    public function getPasswordHashByUsername(string $username) : string
    {
        if (empty($username)) {
            throw new MissingDataException('Username can not be empty.');
        }
        try {
            $statement = "SELECT `password` FROM users WHERE `username` = %s";
            $passwordHash = $this->db->prepare($statement, $username)->getValue();
            return $passwordHash ?? '';
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return '';
        }
    }

    /**
     * Fetches users encryption key and decryptes it with given password.
     *
     * @param string $username
     * @param string $password
     * @return string
     * @throws CryptographyException
     * @throws DatabaseException
     * @throws MissingDataException
     */
    public function getUserKeyByUsername(string $username, string $password) : string
    {
        if (empty($username)) {
            throw new MissingDataException('Username can not be empty.');
        }
        if (empty($password)) {
            throw new MissingDataException('Password can not be empty.');
        }

        $passwordCrypto = new PasswordCrypto;
        $statement = "SELECT `user_key` FROM users WHERE `username` = %s";
        $userKeyEncrypted = $this->db->prepare($statement, $username)->getValue();
        $userKeyDecrypted = $passwordCrypto->decrypt($userKeyEncrypted, $password);
        return $userKeyDecrypted;
    }

    /**
     * Fetches encryption key from database identified by username.
     *
     * @param string $username
     * @return string
     * @throws MissingDataException
     * @throws DatabaseException
     */
    public function getEncryptionKeyByUsername(string $username) : string
    {
        if (empty($username)) {
            throw new MissingDataException('Username can not be empty.');
        }
        $statement = "SELECT `encryption_key` FROM users WHERE `username` = %s";
        return $this->db->prepare($statement, $username)->getValue();
    }

    /**
     * Fetches encryption key for given user.
     *
     * @param string $username
     * @param string $password
     * @return string
     * @throws AuthException
     * @throws MissingDataException
     */
    public function getEncryptionKeyByUsernameAndPassword(string $username, string $password): string
    {
        try {
            $userKey = $this->getUserKeyByUsername($username, $password);
            $encryptionKey = $this->getEncryptionKeyByUsername($username);
            if (empty($encryptionKey)) {
                throw new AuthException('Could not fetch encryption key for given username.');
            }
            $keyCrypto = new KeyCrypto;
            $encryptionKeyDecrypted = $keyCrypto->decryptString($encryptionKey, $userKey);
            if (empty($encryptionKeyDecrypted)) {
                throw new AuthException('Could not decrypt encryption key.');
            }

            return $encryptionKeyDecrypted;
        } catch (CryptographyException $e) {
            $this->logger->error(
                'Cryptography Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            throw new AuthException('Could not get encryption key for username. Cryptography error.');
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            throw new AuthException('Could not get encryption key for username. Database error.');
        }
    }

    /**
     * Fetches users encryption key using username and password from JWT.
     *
     * @param string $token
     * @throws MissingDataException
     * @throws AuthException
     * @return string
     */
    public function getEncryptionKeyFromToken(string $token) : string
    {
        if (empty($token)) {
            throw new MissingDataException('Token can not be empty');
        }

        try {
            $parser = new Parser;
            $parsedToken = $parser->parse($token);
            $username = $parsedToken->getClaim('usr');
            $userEncryptionKey = $parsedToken->getClaim('uek');
            if (empty($username) || empty($userEncryptionKey)) {
                throw new AuthException('Could not get username from token.');
            }
            $encryptionKey = $this->getEncryptionKeyByUsername($username);
            if (empty($encryptionKey)) {
                throw new AuthException('Could not get encryption key.');
            }
            $keyCrypto = new KeyCrypto;
            $encryptionKeyDecrypted = $keyCrypto->decryptString($encryptionKey, $userEncryptionKey);
            if (empty($encryptionKeyDecrypted)) {
                throw new AuthException('Could not decrypt encryption key.');
            }

            return $encryptionKeyDecrypted;
        } catch (CryptographyException $e) {
            $this->logger->error(
                'Cryptography Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            throw new AuthException('Could not get key from token. Cryptography error.');
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            throw new AuthException('Could not get key from token. Database error.');
        }
    }

    /**
     * Creates a new user and saves item to database.
     *
     * @param string $username
     * @param string $password
     * @param string $systemKey
     * @return bool
     * @throws AuthException
     * @throws MissingDataException
     */
    public function createUser(string $username, string $password, string $systemKey) : bool
    {
        if (empty($username)) {
            throw new MissingDataException('Username can not be empty.');
        }
        if (empty($password)) {
            throw new MissingDataException('Password can not be empty.');
        }
        if (empty($systemKey)) {
            throw new MissingDataException('System password can not be empty.');
        }
        try {
            // generate new user key
            $userKey = Key::createNewRandomKey()->saveToAsciiSafeString();

            // encrypt system key with user key:
            $keyCrypto = new KeyCrypto;
            $systemKeyEncrypted = $keyCrypto->encryptString($systemKey, $userKey);

            // encrypt user key with password:
            $passwordCrypto = new PasswordCrypto;
            $userKeyEncrypted = $passwordCrypto->encrypt($userKey, $password);

            // hash password
            $passwordHash = hash('sha256', $password);

            // store new user in database
            $statement = "INSERT INTO users (`username`,`password`,`user_key`,`encryption_key`) VALUES (%s,%s,%s,%s)";
            return $this->db->prepare(
                $statement,
                $username,
                $passwordHash,
                $userKeyEncrypted,
                $systemKeyEncrypted
            )->execute();
        } catch (CryptoException $e) {
            $this->logger->error(
                'Crypto Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        } catch (CryptographyException $e) {
            $this->logger->error(
                'Cryptography Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        }
    }

    /**
     * Saves new system-user to database.
     *
     * @param string $password
     * @return bool
     * @throws MissingDataException
     * @throws CryptographyException
     */
    public function createSystemUser(string $password) : bool
    {
        if (empty($password)) {
            throw new MissingDataException('Password can not be empty.');
        }
        try {
            $passwordHash = hash('sha256', $password);
            $keys = $this->generateSystermUserEncryptionKeys($password);
            if (empty($keys)) {
                return false;
            }
            $statement = "INSERT INTO users (`username`,`password`,`user_key`,`encryption_key`) VALUES (%s,%s,%s,%s)";
            return $this->db->prepare(
                $statement,
                'system',
                $passwordHash,
                $keys['user_key'],
                $keys['encryption_key']
            )->execute();
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }

    /**
     * Update password for given user.
     *
     * @param string $username
     * @param string $password
     * @param string $systemKey
     * @return bool
     */
    public function updateUserPassword(string $username, string $password, string $systemKey): bool
    {
        try {
            // generate new user key
            $userKey = Key::createNewRandomKey()->saveToAsciiSafeString();

            // encrypt system key with user key:
            $keyCrypto = new KeyCrypto;
            $systemKeyEncrypted = $keyCrypto->encryptString($systemKey, $userKey);

            // encrypt user key with password:
            $passwordCrypto = new PasswordCrypto;
            $userKeyEncrypted = $passwordCrypto->encrypt($userKey, $password);

            // hash password
            $passwordHash = hash('sha256', $password);

            // update user
            $statement = "UPDATE users
                SET
                    `password` = %s,
                    `user_key` = %s,
                    `encryption_key` = %s 
                WHERE `username` = %s 
                LIMIT 1";
            return $this->db->prepare(
                $statement,
                $passwordHash,
                $userKeyEncrypted,
                $systemKeyEncrypted,
                $username
            )->execute();
        } catch (CryptoException $e) {
            $this->logger->error(
                'Crypto Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        } catch (CryptographyException $e) {
            $this->logger->error(
                'Cryptography Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );

            return false;
        }
    }

    /**
     * Updates encryption key for system user.
     *
     * @param string $key
     * @throws DatabaseException
     * @throws MissingDataException
     */
    public function updateSystemEncryptionKey(string $key) : void
    {
        if (empty($key)) {
            throw new MissingDataException('Encryption key can not be empty.');
        }
        $statement = "UPDATE users SET `encryption_key` = %s WHERE `username` = 'system'";
        $this->db->prepare($statement, $key)->execute();
    }

    /**
     * Checks if an API password is valid.
     *
     * @param string $apiKey
     * @param string $apiPassword
     * @return bool
     */
    public function apiPasswordIsValid(string $apiKey, string $apiPassword) : bool
    {
        if (empty($apiKey) || empty($apiPassword)) {
            return false;
        }
        try {
            $passwordHash = hash('sha256', $apiPassword . $this->config->get('auth.secret'));
            $statement = "SELECT api_key FROM api_keys WHERE `password` = %s";
            $apiKeyDb = $this->db->prepare($statement, $passwordHash)->getValue();
            if (empty($apiKeyDb)) {
                return false;
            }
            return ($apiKeyDb === $apiKey);
        } catch (DatabaseException $e) {
            $this->logger->error(
                'Database Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }

    /**
     * Generates new encryption key for system user.
     *
     * @param string $password
     * @throws MissingDataException
     * @throws CryptographyException
     * @return array
     */
    private function generateSystermUserEncryptionKeys(string $password) : array
    {
        if (empty($password)) {
            throw new MissingDataException('Password can not be empty.');
        }
        try {
            // generate key:
            $userKey = Key::createNewRandomKey()->saveToAsciiSafeString();
            $systemKey = Key::createNewRandomKey()->saveToAsciiSafeString();

            // encrypt keys:
            $passwordCrypto = new PasswordCrypto;
            $keyCrypto = new KeyCrypto;
            return [
                'user_key' => $passwordCrypto->encrypt($userKey, $password),
                'encryption_key' => $keyCrypto->encryptString($systemKey, $userKey)
            ];
        } catch (CryptoException $e) {
            return [];
        }
    }
}
