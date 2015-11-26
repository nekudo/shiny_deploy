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
     * Fetches master-password hash from database.
     *
     * @return string|boolean
     */
    public function getMasterHash()
    {
        $statement = "SELECT `value` FROM kvstore WHERE `key` = %s";
        $hash = $this->db->prepare($statement, 'mpw_hash')->getValue();
        if (preg_match('#^[a-f0-9]{64}$#', $hash) === 1) {
            return $hash;
        }
        return false;
    }

    /**
     * Saves master-password hash to database.
     *
     * @param string $password
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setMasterPasswordHash($password)
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }
        $passwordHash = hash('sha256', $password);
        $statement = "INSERT INTO kvstore (`key`,`value`) VALUES (%s,%s)";
        return $this->db->prepare($statement, 'mpw_hash', $passwordHash)->execute();
    }

    /**
     * Generates new encrpytion key and stores it in database.
     *
     * @param string $password
     * @return bool
     */
    public function generateEncryptionKey($password)
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password can not be empty.');
        }

        // generate key:
        try {
            $key = Crypto::createNewRandomKey();
        } catch (Ex\CryptoTestFailedException $ex) {
            print_r($ex);
            return false;
        } catch (Ex\CannotPerformOperationException $ex) {
            print_r($ex);
            return false;
        }

        // encrypt key:
        $keyEncryped = $this->encryptString($key, $password);

        // store key in database:
        $statement = "INSERT INTO kvstore (`key`,`value`) VALUES (%s,%s)";
        return $this->db->prepare($statement, 'encryption_key', $keyEncryped)->execute();
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
