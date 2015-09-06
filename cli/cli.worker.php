<?php
if (empty($argv)) {
    exit('Script can only be run in cli mode.' . PHP_EOL);
}
if (empty($argv[1])) {
    exit('No action given. Valid actions are: start|stop|restart|keepalive|status' . PHP_EOL);
}

require_once 'bootstrap.php';

$action = $argv[1];
$workerType = (!empty($argv[2])) ? trim($argv[2]) : '';


$WorkerManager = new \ShinyDeploy\Core\WorkerManager($config);
switch ($action) {
    case 'start':
        echo "Starting workers...\t";
        $response = $WorkerManager->start($workerType);
        echo (($response === true) ? '[OK]' : '[FAILED]') . PHP_EOL;
        break;
    case 'stop':
        echo "Stopping workers...\t";
        $response = $WorkerManager->stop($workerType);
        echo (($response === true) ? '[OK]' : '[FAILED]') . PHP_EOL;
        break;
    case 'restart':
        echo "Restarting workers...\t";
        $response = $WorkerManager->restart($workerType);
        echo (($response === true) ? '[OK]' : '[FAILED]') . PHP_EOL;
        break;
    case 'keepalive':
        $response = $WorkerManager->keepalive();
        break;
    case 'status':
        $response = $WorkerManager->status();
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
