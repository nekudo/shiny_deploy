<?php
require_once 'bootstrap.php';

$context = new ZMQContext;
$zmqSocket = $context->getSocket(ZMQ::SOCKET_PUSH);
$zmqSocket->connect("tcp://localhost:5556");
$pushData = [
    'clientId' => '277b028e-502a-47e8-9866-7160390c7d2b',
    'wsEventName' => 'log',
    'wsEventParams' => [
        'type' => 'info',
        'text' => 'this is just a test',
    ],
];
$zmqSocket->send(json_encode($pushData));
