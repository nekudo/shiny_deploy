<?php

namespace ShinyDeploy\Domain\Updater\Tasks;

use Defuse\Crypto\Key;
use ShinyDeploy\Core\Crypto\KeyCrypto;
use ShinyDeploy\Core\UpdaterTask;
use ShinyDeploy\Core\Crypto\McryptCrypto;
use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Domain\Database\Auth;
use ShinyDeploy\Exceptions\MissingDataException;

class CryptographyMigration extends UpdaterTask
{
    /**
     * Check if cryptography migration needs to be executed.
     *
     * @return bool
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     */
    public function needsExecution(): bool
    {
        $statement = "SHOW COLUMNS FROM `users` LIKE 'user_key'";
        $this->db->prepare($statement)->execute();
        $cnt = $this->db->getResultCount();
        return ($cnt === 0);
    }

    /**
     * Converts old (partially mcrypt based) crypto system to new php 7.2 compatible crypto system.
     *
     * @return void
     * @throws MissingDataException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     */
    public function __invoke(): void
    {
        $this->checkPhpVersion();
        $this->checkMcryptExtension();
        $this->addUserKeyColumn();
        $password = $this->readSystemPassword();
        $this->validateSystemPassword($password);
        $userKey = $this->createUserKey($password);
        $encryptionKey = $this->getEncryptionKey($password);
        $this->storeEncryptionKey($encryptionKey, $userKey);
    }

    /**
     * Checks if script runs with correct PHP version.
     *
     * @return void
     * @throws \RuntimeException
     */
    private function checkPhpVersion() : void
    {
        if (version_compare(PHP_VERSION, '7.1.0', '>=') === false) {
            throw new \RuntimeException('PHP Version has to be 7.1.*');
        }
        if (version_compare(PHP_VERSION, '7.2.0', '<') === false) {
            throw new \RuntimeException('PHP Version has to be 7.1.*');
        }
    }

    /**
     * Check if mcrypt extension is loaded.
     *
     * @return void
     * @throws \RuntimeException
     */
    private function checkMcryptExtension() : void
    {
        if (extension_loaded('mcrypt') === false) {
            throw new \RuntimeException('PHP extension mcrypt is required.');
        }
    }

    /**
     * Creates new table-column to store users encryption key.
     *
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \RuntimeException
     */
    private function addUserKeyColumn() : void
    {
        $statement= "ALTER TABLE users ADD COLUMN user_key BLOB NOT NULL AFTER `password`";
        $res = $this->db->prepare($statement)->execute();
        if ($res === false) {
            throw new \RuntimeException('Could not create user_key column.');
        }
    }

    /**
     * Reads system password from terminal.
     *
     * @return string
     */
    private function readSystemPassword() : string
    {
        fwrite(STDOUT, "Please enter your system password: ");
        $oldStyle = shell_exec('stty -g');
        shell_exec('stty -echo');
        $password = rtrim(fgets(STDIN), "\n");
        shell_exec('stty ' . $oldStyle);
        echo PHP_EOL;
        return $password;
    }

    /**
     * Checks if system password is correct.
     *
     * @param string $password
     * @throws MissingDataException
     */
    private function validateSystemPassword(string $password) : void
    {
        $authDomain = new Auth($this->config, $this->logger);
        $hashFromDatabase = $authDomain->getPasswordHashByUsername('system');
        $hashFromPassword = hash('sha256', $password);
        if ($hashFromPassword !== $hashFromDatabase) {
            throw new \RuntimeException('System password is invalid.');
        }
    }

    /**
     * Creates a new random user-encryption key, encrypts it,  and saves it to database.
     *
     * @param string $password
     * @return string Users encryption key.
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     */
    private function createUserKey(string $password) : string
    {
        $passwordCrypto = new PasswordCrypto;
        $userKey = Key::createNewRandomKey()->saveToAsciiSafeString();
        $keyEncrypted = $passwordCrypto->encrypt($userKey, $password);
        $statement = "UPDATE `users` SET user_key = %s WHERE username = 'system'";
        $this->db->prepare($statement, $keyEncrypted)->execute();
        return $userKey;
    }

    /**
     * Fetches encryption key from database an decrypts it using provided password.
     *
     * @param string $password
     * @return string
     * @throws MissingDataException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     */
    private function getEncryptionKey(string $password) : string
    {
        $authDomain = new Auth($this->config, $this->logger);
        $keyEncrypted = $authDomain->getEncryptionKeyByUsername('system');
        $encryption = new McryptCrypto(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $keyDecrypted = $encryption->decrypt($keyEncrypted, $password);
        return $keyDecrypted;
    }

    /**
     * Encrypts system-encryption-key with user-key and saves it to database.
     *
     * @param string $key
     * @param string $userKey
     * @throws MissingDataException
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     */
    private function storeEncryptionKey(string $key, string $userKey) : void
    {
        $keyCrypto = new KeyCrypto;
        $keyEncrypted = $keyCrypto->encryptString($key, $userKey);
        $authDomain = new Auth($this->config, $this->logger);
        $authDomain->updateSystemEncryptionKey($keyEncrypted);
    }
}
