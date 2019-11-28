<?php
/**
 * This script can be used to control your workers.
 *
 * Call it like e.g.: php control.php start
 */

$validActions = [
    'start',
    'stop',
    'restart',
    'status',
    'keepalive',
    'install',
    'update',
    'user-add',
    'user-change-password',
];

if (empty($argv)) {
    exit('Script can only be run in cli mode.' . PHP_EOL);
}
if (empty($argv[1])) {
    exit(sprintf('No action given. Valid actions are: %s', implode($validActions, '|')) . PHP_EOL);
}

try {
    require_once __DIR__ . '/bootstrap.php';

    $wssManager = new \ShinyDeploy\Core\WssManager($config);
    $managerConfig = $config->get('gears');
    $manager = new \Nekudo\ShinyGears\Manager($managerConfig);
    $action = $argv[1];
    switch ($action) {
        case 'start':
            echo "Starting websocket server...\t";
            echo (($wssManager->start() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;

            $manager->start();
            echo 'Worker processes successfully started' . PHP_EOL;
            break;
        case 'stop':
            echo "Stopping websocket server...\t";
            echo (($wssManager->stop() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;

            $manager->stop();
            echo 'Worker processes successfully stopped.' . PHP_EOL;
            break;
        case 'restart':
            echo "Restarting websocket server...";
            echo (($wssManager->restart() === true) ? '[OK]' : '[FAILED]') . PHP_EOL;

            $manager->restart();
            echo 'Worker processes successfully restarted.' . PHP_EOL;
            break;
        case 'keepalive':
            $wssManager->keepalive();
            $manager->keepalive();
            break;
        case 'install':
            $action = new \ShinyDeploy\Action\CliAction\Install($config, $logger);
            $action->__invoke();
            break;
        case 'update':
            $action = new \ShinyDeploy\Action\CliAction\Update($config, $logger);
            $action->__invoke();
            break;
        case 'user-add':
            $action = new \ShinyDeploy\Action\CliAction\AddUser($config, $logger);
            $action->__invoke();
            break;
        case 'user-change-password':
            $action = new \ShinyDeploy\Action\CliAction\ChangeUserPassword($config, $logger);
            $action->__invoke();
            break;
        case 'status':
            if ($wssManager->status() === true) {
                echo "Websocker server is up.\n\n";
            } else {
                echo "Websocker server is down.\n\n";
            }

            $response = $manager->status();

            echo '+' . str_repeat('-', 85) . '+' . PHP_EOL;
            echo '|' . str_pad('ShinyGears', 85, ' ', STR_PAD_BOTH) . '|' . PHP_EOL;
            echo '|' . str_pad('-= CURRENT WORKER STATUS =-', 85, ' ', STR_PAD_BOTH) . '|' . PHP_EOL;
            echo '+' . str_repeat('-', 85) . '+' . PHP_EOL;

            if (empty($response)) {
                echo '| ' . str_pad('No active workers found.', 84) . '|' . PHP_EOL;
                echo '+' . str_repeat('-', 85) . '+' . PHP_EOL;
                exit;
            }

            echo '| Pool                    | Worker   | S | Jobs Total | ~ Jobs/Min | Uptime | Ping    |' . PHP_EOL;
            echo '+-------------------------+----------+---+------------+------------+--------+---------+' . PHP_EOL;
            foreach ($response as $poolName => $poolInfo) {
                if (empty($poolInfo)) {
                    continue;
                }

                foreach ($poolInfo as $wrokerId => $workerInfo) {
                    echo '| ' . str_pad($poolName, 24);
                    echo '| ' . str_pad($wrokerId, 9);
                    if ($workerInfo['status'] === 'idle') {
                        echo "| \033[0;32mI\033[0m ";
                        echo '| ' . str_pad($workerInfo['jobs_total'], 11);
                        echo '| ' . str_pad($workerInfo['avg_jobs_min'], 11);
                        echo '| ' . str_pad(round($workerInfo['uptime_seconds'] / 3600) . 'h', 7);
                        echo '| ' . str_pad(round($workerInfo['ping'], 3) . 's', 8) . '|' . PHP_EOL;
                    } else {
                        echo "| \033[0;31mB\033[0m ";
                        echo '| ' . str_pad('n/a', 11);
                        echo '| ' . str_pad('n/a', 11);
                        echo '| ' . str_pad('n/a', 7);
                        echo '| ' . str_pad('n/a', 8) . '|' . PHP_EOL;
                    }
                }
            }
            echo '+' . str_repeat('-', 85) . '+' . PHP_EOL;
            echo PHP_EOL;
            break;
        default:
            exit(sprintf('Invalid action. Valid actions are: %s', implode($validActions, '|')) . PHP_EOL);
    }
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
