<?php
require_once 'bootstrap.php';

$logger->info('Starting websocket gateway.');
$WorkerGateway = new ShinyDeploy\Websockets\WsGateway($config, $logger);
$loop = React\EventLoop\Factory::create();
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind($config->get('zmq.dsn'));
$pull->on('message', array($WorkerGateway, 'onApiEvent'));
$webSock = new React\Socket\Server($loop);
$webSock->listen($config->get('wss.port'), $config->get('wss.host'));
$webServer = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            new Ratchet\Wamp\WampServer(
                $WorkerGateway
            )
        )
    ),
    $webSock
);
$loop->run();
