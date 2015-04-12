<?php
return [
    'logging' => [
        'file' => __DIR__ . '/../../logs/general.log',
        'level' => 'debug',
    ],
    'gearman' => [
        'host' => '127.0.0.1',
        'port' => 4730,
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
