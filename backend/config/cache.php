<?php

return [
    // 默认缓存驱动
    'default' => env('CACHE_DRIVER', env('cache.driver', 'redis')),

    // 缓存连接方式配置
    'stores' => [
        'file' => [
            'type' => 'file',
            'path' => runtime_path() . 'cache/',
            'expire' => 0,
        ],
        'redis' => [
            'type' => 'redis',
            'host' => env('redis.host', '127.0.0.1'),
            'port' => env('redis.port', 6379),
            'password' => env('redis.password', ''),
            'select' => env('redis.select', 0),
            'timeout' => 0,
            'expire' => 0,
            'persistent' => false,
            'prefix' => 'daoist_mall:',
        ],
    ],
];
