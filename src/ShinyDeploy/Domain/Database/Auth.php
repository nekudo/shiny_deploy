<?php
namespace ShinyDeploy\Domain\Database;

use Defuse\Crypto\Crypto;
use Exception;
use InvalidArgumentException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use ShinyDeploy\Core\Crypto\PasswordCrypto;

class Auth extends DatabaseDomain
{
    /**
     * Generates JWT.
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    public function generateToken($username, $password, $clientId)
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
    public function validateToken($token, $clientId)
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
     * @return bool
     */
    public function userExists($username)
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
     */
    public function getPasswordHashByUsername($username)
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
     */
    public function getEncryptionKeyByUsername($username)
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
     * @return boolean|string
     * @throws InvalidArgumentException
     */
    public function getEncryptionKeyFromToken($token)
    {
        if (empty($token)) {
            throw new InvalidArgumentException('Token can not be empty');
        }

        try {
            $parser = new Parser;
            $parsedToken = $parser->parse($token);
            $username = $parsedToken->getClaim('usr');
            $passwordEncrypted = $parsedToken->getClaim('pwd');
            if (empty($username) || empty($passwordEncrypted)) {
                return false;
            }
            $encryptionKey = $this->getEncryptionKeyByUsername($username);
            if (empty($encryptionKey)) {
                return false;
            }
            $password = $this->decryptPassword($passwordEncrypted);
            if (empty($password)) {
                return false;
            }
            $encryptionKeyDecrypted = $this->decryptString($encryptionKey, $password);
            if (empty($encryptionKeyDecrypted)) {
                return false;
            }
            return $encryptionKeyDecrypted;
        } catch (Exception $e) {
            $this->logger->error(
                'Token Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
            return false;
        }
    }

    /**
     * Saves new user to database.
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @throws InvalidArgumentException
     */
    public function createUser($username, $password)
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
     * @throws InvalidArgumentException
     */
    public function createSystemUser($password)
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
        $res = $this->db->prepare($statement, 'system', $passwordHash, $encryptionKey)->execute();
        return $res;
    }

    /**
     * Checks if an API password is valid.
     *
     * @param string $apiKey
     * @param string $apiPassword
     * @return bool
     */
    public function apiPasswordIsValid($apiKey, $apiPassword)
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
     * Generates new encrpytion key and stores it in database.
     *
     * @param string $password
     * @return string|bool
     */
    protected function generateEncryptionKey($password)
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }

        // generate key:
        try {
            $key = Crypto::createNewRandomKey();
        } catch (Ex\CryptoTestFailedException $ex) {
            return false;
        } catch (Ex\CannotPerformOperationException $ex) {
            return false;
        }

        // encrypt key:
        $keyEncryped = $this->encryptString($key, $password);
        return $keyEncryped;
    }

    /**
     * Encrypts a password for storage in JWT.
     *
     * @param string $password
     * @return string
     */
    protected function encryptPassword($password)
    {
        $passwordEncrypted = $this->encryptString($password, $this->config->get('auth.secret'));
        $passwordEncryptedEncoded = base64_encode($passwordEncrypted);
        return $passwordEncryptedEncoded;
    }

    /**
     * Decrypts a password from JWT for usage.
     *
     * @param string $encryptedPassword
     * @return string
     */
    protected function decryptPassword($encryptedPassword)
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
    private function encryptString($string, $password)
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
     * @return string
     * @throws InvalidArgumentException
     */
    private function decryptString($string, $password)
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }
        $encryption = new PasswordCrypto(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $stringDecrypted = $encryption->decrypt($string, $password);
        return $stringDecrypted;
    }
}
