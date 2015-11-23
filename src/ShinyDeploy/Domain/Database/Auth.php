<?php
namespace ShinyDeploy\Domain\Database;

use Exception;
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
     * @throws \InvalidArgumentException
     */
    public function setMasterPasswordHash($password)
    {
        if (empty($password)) {
            throw new \InvalidArgumentException('Password can not be empty.');
        }
        $passwordHash = hash('sha256', $password);
        $statement = "INSERT INTO kvstore (`key`,`value`) VALUES (%s,%s)";
        return $this->db->prepare($statement, 'mpw_hash', $passwordHash)->execute();
    }

    /**
     * Encrypts a password for storage in JWT.
     *
     * @param string $password
     * @return string
     */
    protected function encryptPassword($password)
    {
        $encryption = new Encryption(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $passwordEncrypted = $encryption->encrypt($password, $this->config->get('auth.secret'));
        $passwordEncryptedEncoded = base64_encode($passwordEncrypted);
        return $passwordEncryptedEncoded;
    }
}
