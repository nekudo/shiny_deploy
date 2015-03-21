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
     * @param \Ratchet\Wamp\Topic|string $Topic
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
        $data = json_decode($dataEncoded, true);
        // @todo check if data is valid
        if (!isset($this->subscriptions[$data['clientId']])) {
            // @todo log error
            return false;
        }
        $Topic = $this->subscriptions[$data['clientId']];
        $Topic->broadcast($data);
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
            $actionName = $topic->getId();
            $clientId = $params['clientId'];
            $actionClassName = 'ShinyDeploy\Action\\' . ucfirst($actionName);
            $this->logger->debug('WorkerGateway action called: ' . $actionName);
            if (!class_exists($actionClassName)) {
                throw new WebsocketException('Invalid action passed to worker gateway.');
            }
            $action = new $actionClassName;
            $actionCalled = $action->__invoke($params);
            if ($actionCalled === true) {
                $this->wsLog($clientId, 'I successfully triggered the requested action.', 'success');
            } else {
                $this->wsLog($clientId, 'Sry. There was an error while triggering the requested action.', 'error');
            }
        } catch (WebsocketException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
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
        $Topic = $this->subscriptions[$clientId];
        $Topic->broadcast($eventData);
    }
}
