#!/usr/bin/env php
<?php
/**
 * WebSocket服务器启动脚本
 * 
 * 使用方法:
 * php websocket_server.php start         # 启动服务器
 * php websocket_server.php start -d      # 以守护进程方式启动
 * php websocket_server.php stop          # 停止服务器
 * php websocket_server.php restart       # 重启服务器
 * php websocket_server.php status        # 查看服务器状态
 */

namespace think;

// 定义应用目录
define('APP_PATH', __DIR__ . '/app/');

// 加载基础文件
require __DIR__ . '/vendor/autoload.php';

// 加载框架引导文件
require __DIR__ . '/vendor/topthink/framework/src/helper.php';

// 执行HTTP应用并响应
$http = (new App())->http;

// 加载配置
$config = include __DIR__ . '/config/database.php';
\think\facade\Db::setConfig($config);

// 创建WebSocket服务器实例
$wsConfig = include __DIR__ . '/config/websocket.php';

// 从环境变量或配置文件获取配置
$host = getenv('WEBSOCKET_HOST') ?: $wsConfig['host'];
$port = getenv('WEBSOCKET_PORT') ?: $wsConfig['port'];

$server = new \app\websocket\ChatWebSocketServer($host, $port);

// 启动服务器
$server->start();
