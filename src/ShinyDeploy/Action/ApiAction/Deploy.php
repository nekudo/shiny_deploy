<?php namespace ShinyDeploy\Action\ApiAction;

use RuntimeException;
use ShinyDeploy\Core\Crypto\PasswordCrypto;
use ShinyDeploy\Domain\Database\ApiKeys;
use ShinyDeploy\Domain\Database\DeploymentLogs;
use ShinyDeploy\Domain\Database\Deployments;
use ShinyDeploy\Responder\NullResponder;

class Deploy extends ApiAction
{

    /**
     * @param array $requestParameters
     * @return bool
     * @throws \ShinyDeploy\Exceptions\CryptographyException
     * @throws \ShinyDeploy\Exceptions\DatabaseException
     * @throws \ShinyDeploy\Exceptions\MissingDataException
     * @throws \ZMQException
     */
    public function __invoke(array $requestParameters = []): bool
    {
        $apiKeys = new ApiKeys($this->config, $this->logger);
        $apiKeyData = $apiKeys->getDataByApiKey($this->apiKey);
        if (empty($apiKeyData)) {
            throw new RuntimeException('API key not found in database.');
        }

        // decrypt encryption key:
        $encryption = new PasswordCrypto();
        $decryptionPassword = $this->apiPassword . $this->config->get('auth.secret');
        $encryptionKey = $encryption->decrypt($apiKeyData['encryption_key'], $decryptionPassword);

        // get deployment:
        $deployments = new Deployments($this->config, $this->logger);
        $deployments->setEnryptionKey($encryptionKey);
        $deployment = $deployments->getDeployment($apiKeyData['deployment_id']);

        // check if branches match
        if (!empty($requestParameters['branch'])) {
            if ($deployment->isBranch($requestParameters['branch']) === false) {
                $this->logger->info('API Deployment skipped. Wrong branch.');
                return false;
            }
        }

        // log start of deployment:
        $deploymentLogs = new DeploymentLogs($this->config, $this->logger);
        $logId = $deploymentLogs->logDeploymentStart($apiKeyData['deployment_id'], 'API');

        // start requested deploy:
        $nullResponder = new NullResponder($this->config, $this->logger);
        $deployment->setLogResponder($nullResponder);
        $result = $deployment->deploy(false);

        // log result:
        if ($result === true) {
            $deploymentLogs->logDeploymentSuccess($logId);
            $this->logger->info('API Deployment succeeded.');
        } else {
            $deploymentLogs->logDeploymentError($logId);
            $this->logger->error('API Deployment failed.');
        }

        return true;
    }
}
