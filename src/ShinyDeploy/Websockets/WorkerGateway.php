<?php
namespace ShinyDeploy\Websockets;

use Apix\Log\Logger;
use Noodlehaus\Config;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use ShinyDeploy\Exceptions\WebsocketException;

class WorkerGateway implements WampServerInterface
{
    /** @var Config $config */
    protected $config;

    /** @var  Logger $logger */
    protected $logger;

    /** @var array $subscriptions */
    protected $subscriptions = [];

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Adds new client to list of subscribers.
     *
     * @param ConnectionInterface $conn
     * @param \Ratchet\Wamp\Topic $Topic
     */
    public function onSubscribe(ConnectionInterface $conn, $Topic)
    {
        $clientId = $Topic->getId();
        if (!array_key_exists($clientId, $this->subscriptions)) {
            $this->subscriptions[$clientId] = $Topic;
        }
    }

    /**
     * This method is called whenever a message is pushed into the server using ZMQ.
     *
     * @param string $dataEncoded Json Encode data passed by ZMQ.
     * @return bool
     */
    public function onApiEvent($dataEncoded)
    {
        try {
            $this->logger->debug('onApiEvent: ' . $dataEncoded);
            $data = json_decode($dataEncoded, true);
            if (empty($data['clientId']) || empty($data['eventName'])) {
                throw new WebsocketException('Required parameter missing.');
            }
            if (!isset($this->subscriptions[$data['clientId']])) {
                throw new WebsocketException('Invalid client-id.');
            }
            /** @var \Ratchet\Wamp\Topic $Topic */
            $Topic = $this->subscriptions[$data['clientId']];
            $Topic->broadcast($data);
            return true;
        } catch (WebsocketException $e) {
            $this->logger->alert(
                'Gateway Error: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
        return false;
    }

    /**
     * This methods handles events triggered by the client/browser.
     *
     * @param ConnectionInterface $conn
     * @param string $id
     * @param \Ratchet\Wamp\Topic|string $topic
     * @param array $params
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        try {
            $this->logger->debug('onCall: ' . json_encode($params));
            $actionName = $topic->getId();
            $clientId = $params['clientId'];
            $callbackId = (isset($params['callbackId'])) ? $params['callbackId'] : null;

            if (!empty($callbackId)) {
                $actionPayload = (isset($params['actionPayload'])) ? $params['actionPayload'] : [];
                $this->handleCallbackRequest($clientId, $callbackId, $actionName, $actionPayload);
            } else {
                $this->handleTriggerRequest($clientId, $actionName, $params);
            }
        } catch (WebsocketException $e) {
            $this->logger->alert(
                'Gateway Error: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
    }

    /**
     * Handles requests which directly respond with the requested data.
     */
    protected function handleCallbackRequest($clientId, $callbackId, $actionName, array $actionPayload)
    {
        $actionClassName = 'ShinyDeploy\Action\\' . ucfirst($actionName);
        if (!class_exists($actionClassName)) {
            throw new WebsocketException('Invalid action passed to worker gateway.');
        }
        $action = new $actionClassName($this->config, $this->logger);
        $actionResponse = $action->__invoke($actionPayload);
        $response = [
            'callbackId' => $callbackId,
            'payload' => $actionResponse
        ];
        $Topic = $this->subscriptions[$clientId];
        $Topic->broadcast($response);
    }

    /**
     * Handles requests which just trigger an action. Response data (if any) will be send
     * later form within the action itself.
     */
    protected function handleTriggerRequest($clientId, $actionName, $params)
    {
        $actionClassName = 'ShinyDeploy\Action\\' . ucfirst($actionName);
        if (!class_exists($actionClassName)) {
            throw new WebsocketException('Invalid action passed to worker gateway.');
        }
        $action = new $actionClassName($this->config, $this->logger);
        $actionResponse = $action->__invoke($params);
        if ($actionResponse === true) {
            $this->wsLog($clientId, 'I successfully triggered the requested action.', 'success');
        } else {
            $this->wsLog($clientId, 'Sry. There was an error while triggering the requested action.', 'error');
        }
    }

    /**
     * Closes connection cause "onPublish" event is not used within this application.
     *
     * @param ConnectionInterface $conn
     * @param \Ratchet\Wamp\Topic|string $topic
     * @param string $event
     * @param array $exclude
     * @param array $eligible
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $conn->close();
    }

    public function onUnSubscribe(ConnectionInterface $conn, $Topic)
    {
    }

    public function onOpen(ConnectionInterface $conn)
    {
    }

    public function onClose(ConnectionInterface $conn)
    {
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }

    /**
     * Sends a log-event response to client.
     *
     * @param string $clientId
     * @param string $msg
     * @param string $type
     * @throws WebsocketException
     */
    protected function wsLog($clientId, $msg, $type = 'default')
    {
        if (empty($clientId)) {
            throw new WebsocketException('Invalid client id.');
        }
        $eventData = [
            'clientId' => $clientId,
            'wsEventName' => 'log',
            'wsEventParams' => [
                'text' => $msg,
                'type' => $type,
                'source' => 'WsGateway'
            ],
        ];
        /** @var \Ratchet\Wamp\Topic $Topic */
        $Topic = $this->subscriptions[$clientId];
        $Topic->broadcast($eventData);
    }
}
