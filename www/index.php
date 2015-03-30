<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../cli/bootstrap.php';

$slim = new \Slim\Slim;
$slim->config('debug', true);

// Home route
$slim->get(
    '/',
    function () use ($slim, $config, $logger) {
        $showHomepageAction = new \ShinyDeploy\Action\ShowHomepage($config, $logger);
        $showHomepageAction->setSlim($slim);
        $showHomepageAction->__invoke();
    }
);

// Server routes
$slim->get(
    '/servers',
    function () use ($slim, $config, $logger) {
        $listServersAction = new \ShinyDeploy\Action\ListServers($config, $logger);
        $listServersAction->setSlim($slim);
        $listServersAction->__invoke();
    }
);

// Repositories routes
$slim->get(
    '/repositories',
    function () use ($slim, $config, $logger) {
        $listServersAction = new \ShinyDeploy\Action\ListRepositories($config, $logger);
        $listServersAction->setSlim($slim);
        $listServersAction->__invoke();
    }
);

// let's roll
$slim->run();
