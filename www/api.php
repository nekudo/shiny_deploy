<?php

require_once __DIR__ . '/../cli/bootstrap.php';
$api = new ShinyDeploy\Core\RestApi($config, $logger);
$api->handleRequest();