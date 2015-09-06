<?php
return [
    'logging' => [
        'file' => __DIR__ . '/../../logs/general.log',
        'level' => 'debug',
    ],
    'gearman' => [
        'host' => '127.0.0.1',
        'port' => 4730,

        'workerPath' => __DIR__ . '/../../cli/',
        'pidPath' => __DIR__ . '/../../cli/run/',
        'logPath' => __DIR__ . '/../../logs/',

        'processIdentifier' => 'cli/worker',
        'timeTillGhost' => 1200,

        // Worker startup configuration:
        'workerScripts' => [
            'deployer' => [
                'filename' => 'worker.deployer.php',
                'instances' => 3,
            ],
            'repoactions' => [
                'filename' => 'worker.repo_actions.php',
                'instances' => 3,
            ],
        ]
    ],
    'zmq' => [
        'dsn' => 'tcp://127.0.0.1:5556',
    ],
    'wss' => [
        'host' => '0.0.0.0',
        'port' => 8090,
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
    'sources' => [
        'source1' => [
            'title' => 'Source 1',
            'type' => 'git',
            'url' => 'https://github.com/foo/bar.git',
        ],
    ],
    'targets' => [
        'target1' => [
            'title' => 'Target 1',
            'type' => 'sftp',
            'credentials' => [
                'host' => 'host',
                'port' => 22,
                'user' => 'user',
                'pass' => 'pass'
            ],
        ],
    ],
];
