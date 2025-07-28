<?php
return [
    'consumer' => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 8, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir'        => app_path() . '/queue',
        ],
    ],
    'consumer_common' => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 8,
        'constructor' => [
            'consumer_dir'        => app_path() . '/common/queue',
        ],
    ],
];
