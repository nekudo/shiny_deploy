<?php
return [
    'auth' => [
        'secret' => 'Np5IjAAgx7jOyIUdoEMf',
        'check' => '',
    ],
    'logging' => [
        'file' => __DIR__ . '/../../logs/general.log',
        'level' => 'debug',
    ],
    'gearman' => [
        'host' => '127.0.0.1',
        'port' => 4730,

        'workerPath' => __DIR__ . '/Worker/',
        'pidPath' => __DIR__ . '/../../cli/run/',
        'logPath' => __DIR__ . '/../../logs/',

        'timeTillGhost' => 1200,

        // Worker startup configuration:
        'workerScripts' => [
            'deployer' => [
                'classname' => 'ShinyDeploy\Worker\Deployer',
                'filename' => 'Deployer.php',
                'instances' => 5,
            ],
            'repoactions' => [
                'classname' => 'ShinyDeploy\Worker\RepositoryActions',
                'filename' => 'RepositoryActions.php',
                'instances' => 5,
            ],
        ]
    ],
    'zmq' => [
        'dsn' => 'tcp://127.0.0.1:5556',
    ],
    'wss' => [
        'host' => '0.0.0.0',
        'port' => 8090,

        'wssPath' => __DIR__ . '/../../cli/scripts/',
        'logPath' => __DIR__ . '/../../logs/',
        'processIdentifier' => 'scripts/wss',
    ],
    'repositories' => [
        'path' => __DIR__ . '/../../repositories',
    ],
    'db' => [
        'host' => 'localhost',
        'user' => '',
        'pass' => '',
        'db' => 'my_database',
    ],
    'git' => [
        'name' => 'John Doe',
        'email' => 'john@needsnomail.com',
    ]
];
