<?php
require_once 'bootstrap.php';

$context = new ZMQContext;
$zmqSocket = $context->getSocket(ZMQ::SOCKET_PUSH);
$zmqSocket->connect("tcp://localhost:5556");
$pushData = [
    'action' => 'log',
    'actionData' => 'whatever...',
];
$zmqSocket->send(json_encode($pushData));
