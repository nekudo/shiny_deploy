<?php namespace ShinyDeploy\Action\ApiAction;

use RuntimeException;
use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Domain\Database\ApiKeys;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Domain\Deployment;
use ShinyDeploy\Responder\NullResponder;

class Deploy extends ApiAction
{

    public function __invoke(array $requestParameters = [])
    {
        $apiKeys = new ApiKeys($this->config, $this->logger);
        $apiKeyData = $apiKeys->getDataByApiKey($this->apiKey);
        if (empty($apiKeyData)) {
            throw new RuntimeException('API key not found in database.');
        }

        // decrypt encryption key:
        $encryption = new PasswordCrypto(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $decryptionPassword = $this->apiPassword . $this->config->get('auth.secret');
        $encryptionKey = $encryption->decrypt($apiKeyData['encryption_key'], $decryptionPassword);

        // get deployment:
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        /** @var Deployment $deployment */
        $deployment = $deployments->getDeployment($apiKeyData['deployment_id']);

        // check if branches match
        if (!empty($requestParameters['branch'])) {
            if ($deployment->isBranch($requestParameters['branch']) === false) {
                $this->logger->info('API Deployment skipped. Wrong branch.');
                return false;
            }
        }

        // start requested deploy:
        $nullResponder = new NullResponder($this->config, $this->logger);
        $deployment->setLogResponder($nullResponder);
        $result = $deployment->deploy(false);
        if ($result === true) {
            $this->logger->info('API Deployment succeeded.');
        } else {
            $this->logger->error('API Deployment failed.');
        }
    }
}
