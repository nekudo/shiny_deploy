<?php
// worker name is required:
$shortOpts = 'h';
$longOpts = [
    'help',
    'name:',
];
$options = getopt($shortOpts, $longOpts);
if (isset($options['h']) || isset($options['help'])) {
    echo "Params:".PHP_EOL;
    echo "--name <name>		Unique name to identify the worker.".PHP_EOL;
    exit;
}
if (!isset($options['name'])) {
    echo "Worker name is required. Use -h option for help.".PHP_EOL;
    exit;
}

// startup worker:
require_once __DIR__ . '/../bootstrap.php';

$deployer = new \ShinyDeploy\Worker\Deployer(
    $options['name'],
    $config,
    $logger
);
