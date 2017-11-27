<?php

require_once __DIR__ . '/../vendor/autoload.php';

// load config:
$config = \Noodlehaus\Config::load(__DIR__ . '/../config/config.php');

// init logger:
$logger = new \Apix\Log\Logger;
$fileLogger = new \Apix\Log\Logger\File($config->get('logging.file'));
$fileLogger->setMinLevel($config->get('logging.level'));
$logger->add($fileLogger);
