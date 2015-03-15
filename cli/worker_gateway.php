<?php
require_once 'bootstrap.php';

$logger->info('Starting worker gateway.');
$WorkerGateway = new ShinyDeploy\Websockets\WorkerGateway($config, $logger);
$loop = React\EventLoop\Factory::create();
//$loop->addPeriodicTimer(60, array($MaxloadStatus, 'updateWorkerStatus'));
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5556'); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($WorkerGateway, 'onApiEvent'));
$webSock = new React\Socket\Server($loop);
$webSock->listen(8090, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
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
