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

    public function onSubscribe(ConnectionInterface $conn, $Topic)
    {
        $topicId = $Topic->getId();
        if (!array_key_exists($topicId, $this->subscriptions)) {
            $this->subscriptions[$topicId] = $Topic;
        }
    }

    /**
     * This method is called whenever a message is pushed into the server using ZMQ.
     * @param string $dataEncoded Json Encode data passed by ZMQ.
     */
    public function onApiEvent($dataEncoded)
    {
        $eventData = json_decode($dataEncoded, true);
        $action = $eventData['action'];
        $actionData = $eventData['actionData'];
        switch ($action) {
            case 'log':
                $this->log($actionData);
                break;
            default:
                // unknown action
                break;
        }
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

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        try {
            $actionName = $topic->getId();
            $actionClassName = 'ShinyDeploy\Action\\' . ucfirst($actionName);
            $this->logger->debug('WorkerGateway action called: ' . $actionName);
            if (!class_exists($actionClassName)) {
                throw new WebsocketException('Invalid action passed to worker gateway.');
            }
            $action = new $actionClassName;
            $actionResonse = $action->__invoke($params);
            //$response = json_encode(['callresponse' => 'foo bar baz']);
            //$conn->event($clientId, $response);
        } catch (WebsocketException $e) {
            $this->logger->alert(
                'Worker Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ': ' . $e->getLine() . ')'
            );
        }
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        // onPublished not used in this app.
        $conn->close();
    }
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }

    protected function log($data)
    {
        if (!isset($this->subscriptions['wg.default'])) {
            return true;
        }
        $Topic = $this->subscriptions['wg.default'];
        $Topic->broadcast(
            array(
                'action' => 'log',
                'actionData' => $data,
            )
        );
    }
}
