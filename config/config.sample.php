<?php
return [
    'auth' => [
        'secret' => '', // set a random string to use as encryption password
    ],

    'logging' => [
        'file' => __DIR__ . '/../logs/general.log',
        'level' => 'warning',
        'maxDeploymentLogs' => 50,
    ],

    'gears' => [
        'gearman' => [
            'host' => '127.0.0.1',
            'port' => 4730,
        ],

        'paths' => [
            'config' => __FILE__,
            'log' => __DIR__ . '/../logs',
            'run' => __DIR__ . '/../cli/run',
        ],

        'pools' => [
            'deploymentactions' => [
                'worker_file' => __DIR__ . '/../src/ShinyDeploy/Worker/DeploymentActions.php',
                'worker_class' => '\ShinyDeploy\Worker\DeploymentActions',
                'instances' => 1,
            ],

            'repoactions' => [
                'worker_file' => __DIR__ . '/../src/ShinyDeploy/Worker/RepositoryActions.php',
                'worker_class' => '\ShinyDeploy\Worker\RepositoryActions',
                'instances' => 2,
            ],
        ]
    ],

    'zmq' => [
        'dsn' => 'tcp://127.0.0.1:5556',
    ],

    'wss' => [
        'host' => '0.0.0.0',
        'port' => 8090,

        'wssPath' => __DIR__ . '/../cli/scripts/',
        'logPath' => __DIR__ . '/../logs/',
        'processIdentifier' => 'scripts/wss',
    ],

    'repositories' => [
        'path' => __DIR__ . '/../repositories',
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
    ],

    'api' => [
        'requestParser' => [
            'github',
            'bitbucket',
            'gitea',
        ],
    ],
];
