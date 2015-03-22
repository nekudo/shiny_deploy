<?php

require_once __DIR__ . '/../vendor/autoload.php';

// load config:
$config = \Noodlehaus\Config::load(__DIR__ . '/../src/ShinyDeploy/config.php');

// init logger:
$pathLogfile = __DIR__ . '/../logs/' . $config->get('logging.file');
$logger = new \Apix\Log\Logger;
$fileLogger = new \Apix\Log\Logger\File($pathLogfile);
$fileLogger->setMinLevel($config->get('logging.level'));
$logger->add($fileLogger);
