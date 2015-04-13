<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\Responder;
use ShinyDeploy\Exceptions\WebsocketException;

class WsDataResponder extends Responder
{
    /**
     * @var string $callbackId
     */
    protected $callbackId;

    /**
     * @var string $responseType
     */
    protected $responseType = 'success';

    /**
     * @var string $errorMsg
     */
    protected $errorMsg = '';

    /**
     * @var array $payload
     */
    protected $payload = [];

    /**
     * Sets callback id.
     *
     * @param string $callbackId
     * @return bool
     */
    public function setCallbackId($callbackId)
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
     * @throws WebsocketException
     */
    public function setError($msg = '')
    {
        if (!is_string($msg)) {
            throw new WebsocketException('Error message has to be of type string.');
        }
        $this->$responseType = 'error';
        if (!empty($msg)) {
            $this->errorMsg = $msg;
        }
    }

    /**
     * Sets payload data.
     *
     * @param mixed $data
     */
    public function setPayload($data)
    {
        $this->payload = $data;
    }

    /**
     * Returns data to send to client.
     *
     * @return array
     */
    public function getFrameData()
    {
        $frameData = [
            'callbackId' => $this->callbackId,
            'type' => $this->$responseType,
        ];
        if ($this->$responseType === 'error') {
            $frameData['reason'] = $this->errorMsg;
        } else {
            $frameData['payload'] = $this->payload;
        }
        return $frameData;
    }
}
