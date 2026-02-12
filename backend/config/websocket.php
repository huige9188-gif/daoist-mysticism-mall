<?php
/**
 * WebSocket服务器配置
 */

return [
    // WebSocket服务器监听地址
    'host' => '0.0.0.0',
    
    // WebSocket服务器监听端口
    'port' => 2346,
    
    // Worker进程数
    'worker_count' => 4,
    
    // 心跳检测间隔（秒）
    'heartbeat_interval' => 30,
    
    // 连接超时时间（秒）
    'connection_timeout' => 300,
    
    // 是否启用SSL
    'ssl' => false,
    
    // SSL证书路径
    'ssl_cert' => '',
    
    // SSL密钥路径
    'ssl_key' => '',
];
