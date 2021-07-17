<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\Responder;
use ShinyDeploy\Exceptions\WebsocketException;

class WsDataResponder extends Responder
{
    /**
     * @var int $callbackId
     */
    protected int $callbackId;

    /**
     * @var string $responseType
     */
    protected string $responseType = 'success';

    /**
     * @var string $errorMsg
     */
    protected string $errorMsg = '';

    /**
     * @var array $payload
     */
    protected array $payload = [];

    /**
     * Sets callback id.
     *
     * @param int $callbackId
     * @return bool
     */
    public function setCallbackId(int $callbackId): bool
    {
        if (empty($callbackId)) {
            return false;
        }
        $this->callbackId = $callbackId;
        return true;
    }

    /**
     * Sets an error message. Automatically switches type to "error".
     *
     * @param string $msg
     * @return void
     * @throws WebsocketException
     */
    public function setError(string $msg = ''): void
    {
        if (!is_string($msg)) {
            throw new WebsocketException('Error message has to be of type string.');
        }
        $this->responseType = 'error';
        if (!empty($msg)) {
            $this->errorMsg = $msg;
        }
    }

    /**
     * Sets payload data.
     *
     * @param mixed $data
     * @return void
     */
    public function setPayload($data): void
    {
        $this->payload = $data;
    }

    /**
     * Returns data to send to client.
     *
     * @return array
     */
    public function getFrameData(): array
    {
        $frameData = [
            'callbackId' => $this->callbackId,
            'type' => $this->responseType,
        ];
        if ($this->responseType === 'error') {
            $frameData['reason'] = $this->errorMsg;
        } else {
            $frameData['payload'] = $this->getSecurePayload();
        }
        return $frameData;
    }

    /**
     * Returns payload with all protected/secure fields removed.
     *
     * @return array
     */
    protected function getSecurePayload(): array
    {
        $protectedFields = ['password'];
        $securePayload = $this->payload;
        foreach ($protectedFields as $protectedField) {
            $this->unsetProtectedField($securePayload, $protectedField);
        }

        return $securePayload;
    }

    /**
     * Recursively removes given field/key from an array.
     *
     * @param array $payload
     * @param string $protectedField
     */
    private function unsetProtectedField(array &$payload, string $protectedField): void
    {
        unset($payload[$protectedField]);
        foreach ($payload as &$value) {
            if (is_array($value)) {
                $this->unsetProtectedField($value, $protectedField);
            }
        }
    }
}
