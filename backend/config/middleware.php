<?php

return [
    // 全局中间件
    'alias' => [
        'auth' => app\middleware\AuthMiddleware::class,
        'admin' => app\middleware\AdminMiddleware::class,
        'cors' => app\middleware\CorsMiddleware::class,
        'json' => app\middleware\JsonResponseMiddleware::class,
    ],
];
