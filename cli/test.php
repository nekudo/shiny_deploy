<?php
require_once 'bootstrap.php';

$responder = new \ShinyDeploy\Responder\WsLogResponder($config, $logger);
$responder->setClientId('f2301e92-8f0d-43cd-ab00-dc873e2a8990');
$responder->log('Just a test..', 'info', 'Testing Script');
