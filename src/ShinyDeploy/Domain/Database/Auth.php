<?php
namespace ShinyDeploy\Domain\Database;

use Defuse\Crypto\Crypto;
use Exception;
use InvalidArgumentException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use ShinyDeploy\Domain\Encryption;

class Auth extends DatabaseDomain
{
    /**
     * Generates JWT.
     *
     * @param string $password
     * @return string
     */
    public function generateToken($password, $clientId)
    {
        try {
            $signer = new Sha256;
            $builder = new Builder;
            $builder->setIssuer('ShinyDeploy')
                ->setId($clientId, true)
                ->setIssuedAt(time())
                ->setNotBefore(time())
                ->setExpiration(time() + 3600*8)
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
            var_dump($ex);
            return false;
        } catch (Ex\CannotPerformOperationException $ex) {
            var_dump($ex);
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
        $encryption = new Encryption(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $stringEncrypted = $encryption->encrypt($string, $password);
        return $stringEncrypted;
    }
}
