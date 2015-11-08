<?php
if (empty($argv)) {
    exit('Script can only be run in cli mode.' . PHP_EOL);
}
if (empty($argv[1])) {
    exit('No action given. Valid actions are: start|stop|restart|keepalive|status' . PHP_EOL);
}

require_once 'bootstrap.php';

$action = $argv[1];

$angela = new Nekudo\Angela\Angela;
$angela->setGearmanCredentials($config['gearman.host'], $config['gearman.port']);
$angela->setWorkerPath($config['gearman.workerPath']);
$angela->setLogPath($config['gearman.logPath']);
$angela->setRunPath($config['gearman.pidPath']);
$angela->setWorkerConfig($config['gearman.workerScripts']);
$wssManager = new \ShinyDeploy\Core\WssManager($config);
switch ($action) {
    case 'start':
        echo "Starting websocket server...\t";
        echo (($wssManager->start() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;

        echo "Starting workers...\t";
        echo (($angela->start() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;
        break;
    case 'stop':
        echo "Stopping websocket server...\t";
        echo (($wssManager->stop() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;

        echo "Stopping workers...\t";
        echo (($angela->stop() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;
        break;
    case 'restart':
        echo "Restarting websocket server...";
        echo (($wssManager->restart() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;

        echo "Restarting workers...\t";
        echo (($angela->restart() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;
        break;
    case 'keepalive':
        $wssManager->keepalive();
        $angela->keepalive();
        break;
    case 'status':
        if ($wssManager->status() === true) {
            echo "Websocker server is up.\n\n";
        } else {
            echo "Websocker server is down.\n\n";
        }

        $response = $angela->status();
        if (empty($response)) {
            echo "No workers running." . PHP_EOL;
            exit;
        }
        echo "\n### Currently active workers:\n\n";
        foreach ($response as $workerName => $workerData) {
            if ($workerData=== false) {
                $responseString = 'not responding';
            } else {
                $responseString = 'Ping: ' . round($workerData['ping'], 4)."s\t Jobs: " . $workerData['jobs_total'] .
                    ' ('. $workerData['avg_jobs_min'] . '/min) ';
            }
            echo $workerName . ":\t\t[" . $responseString . "]" . PHP_EOL;
        }
        break;
    default:
        exit('Invalid action. Valid actions are: start|stop|restart|keepalive' . PHP_EOL);
        break;
}
