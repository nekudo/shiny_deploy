<?php
require_once __DIR__ . '/../bootstrap.php';

$logger->info('Starting websocket gateway.');
$WorkerGateway = new ShinyDeploy\Core\WsGateway($config, $logger);
$loop = React\EventLoop\Factory::create();
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind($config->get('zmq.dsn'));
$pull->on('message', array($WorkerGateway, 'onApiEvent'));
$uri = $config->get('wss.host') .':'.$config->get('wss.port');
$webSock = new React\Socket\Server($uri, $loop);
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
