<?php
require_once __DIR__ . '/../bootstrap.php';

$action = new \ShinyDeploy\Action\CliAction\Update($config, $logger);
$action->__invoke();
