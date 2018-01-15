<?php
require_once __DIR__ . '/../bootstrap.php';

$dbConfig = $config->get('db');
$db = new \ShinyDeploy\Core\Db($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);
$statement = "SELECT * FROM `users` WHERE username = %s AND id = %i";
$tmp = $db->prepare($statement, 'system', 1)->getResult();
print_r($tmp);
