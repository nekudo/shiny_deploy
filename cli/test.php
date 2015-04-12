<?php
require_once 'bootstrap.php';

$context = new ZMQContext;
$zmqSocket = $context->getSocket(ZMQ::SOCKET_PUSH);
$zmqSocket->connect("tcp://localhost:5556");
$pushData = [
    'clientId' => 'f2301e92-8f0d-43cd-ab00-dc873e2a8990',
    'eventName' => 'log',
    'eventPayload' => [
        'type' => 'info',
        'text' => 'this is just a test',
    ],
];
$zmqSocket->send(json_encode($pushData));
