# WebSocket服务器设置指南

## 概述

本项目使用Workerman实现WebSocket服务器，用于实时聊天功能。

**验证需求:** 10.2, 10.3

## 安装依赖

首先需要安装Workerman依赖：

```bash
cd backend
composer install
```

这将安装`workerman/workerman`包。

## 配置

### 1. 环境变量配置

复制`.env.example`到`.env`（如果还没有）：

```bash
cp .env.example .env
```

在`.env`文件中配置WebSocket服务器参数：

```ini
[WEBSOCKET]
HOST = 0.0.0.0
PORT = 2346
WORKER_COUNT = 4
SSL = false
SSL_CERT = 
SSL_KEY = 
```

**配置说明:**

- `HOST`: WebSocket服务器监听地址，`0.0.0.0`表示监听所有网络接口
- `PORT`: WebSocket服务器监听端口，默认2346
- `WORKER_COUNT`: Worker进程数，建议设置为CPU核心数
- `SSL`: 是否启用SSL/TLS加密，生产环境建议启用
- `SSL_CERT`: SSL证书文件路径（启用SSL时需要）
- `SSL_KEY`: SSL密钥文件路径（启用SSL时需要）

### 2. 数据库配置

确保数据库配置正确，WebSocket服务器需要访问数据库来保存和查询消息：

```ini
[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = daoist_mall
USERNAME = root
PASSWORD = 
HOSTPORT = 3306
CHARSET = utf8mb4
```

## 启动服务器

### 开发环境

在开发环境中，可以前台运行服务器以便查看日志：

```bash
php websocket_server.php start
```

### 生产环境

在生产环境中，建议以守护进程方式运行：

```bash
php websocket_server.php start -d
```

## 管理服务器

### 查看状态

```bash
php websocket_server.php status
```

输出示例：
```
Workerman[websocket_server.php] status
---------------------------------------
Workerman version:4.0.0          PHP version:7.4.0
start time:2024-01-01 12:00:00   run 0 days 1 hours
load average: 0.5, 0.4, 0.3
4 workers       16 processes
worker_name       exit_status      exit_count
ChatWebSocketServer   0                0
```

### 停止服务器

```bash
php websocket_server.php stop
```

### 重启服务器

```bash
php websocket_server.php restart
```

### 平滑重启

平滑重启不会断开现有连接：

```bash
php websocket_server.php reload
```

## 测试连接

### 使用浏览器测试

在浏览器控制台中运行：

```javascript
// 连接WebSocket服务器
const ws = new WebSocket('ws://localhost:2346');

// 连接成功
ws.onopen = function() {
    console.log('连接成功');
    
    // 认证
    ws.send(JSON.stringify({
        type: 'auth',
        user_id: 1
    }));
};

// 接收消息
ws.onmessage = function(event) {
    console.log('收到消息:', JSON.parse(event.data));
};

// 连接错误
ws.onerror = function(error) {
    console.error('连接错误:', error);
};

// 连接关闭
ws.onclose = function() {
    console.log('连接关闭');
};
```

### 使用命令行测试

可以使用`wscat`工具测试：

```bash
# 安装wscat
npm install -g wscat

# 连接服务器
wscat -c ws://localhost:2346

# 发送认证消息
> {"type":"auth","user_id":1}

# 加入会话
> {"type":"join_session","session_id":1}

# 发送消息
> {"type":"send_message","session_id":1,"content":"测试消息"}
```

## 架构说明

### 组件结构

```
backend/
├── app/
│   └── websocket/
│       └── ChatWebSocketServer.php    # WebSocket服务器主类
├── config/
│   └── websocket.php                  # WebSocket配置文件
├── docs/
│   └── WEBSOCKET_API.md              # WebSocket API文档
├── websocket_server.php              # 服务器启动脚本
└── README_WEBSOCKET.md               # 本文档
```

### 工作原理

1. **连接管理**: 服务器维护三个映射表
   - `$connections`: 所有活跃连接
   - `$userConnections`: 用户ID到连接的映射
   - `$sessionConnections`: 会话ID到连接的映射

2. **消息处理**: 
   - 客户端发送消息到服务器
   - 服务器解析消息类型并调用相应的处理方法
   - 处理方法保存消息到数据库
   - 服务器广播消息到会话中的所有连接

3. **会话管理**:
   - 客户端加入会话后，连接被添加到会话映射表
   - 会话中的消息会广播给所有在该会话中的连接
   - 客户端离开会话或断开连接时，从映射表中移除

### 消息流程

```
用户A                WebSocket服务器              用户B/客服
  |                        |                        |
  |------ 连接 ----------->|                        |
  |<--- connected ---------|                        |
  |                        |                        |
  |------ auth ----------->|                        |
  |<-- auth_success -------|                        |
  |                        |                        |
  |--- join_session ------>|                        |
  |<-- joined_session -----|                        |
  |                        |<------ 连接 -----------|
  |                        |---- connected -------->|
  |                        |                        |
  |                        |<------ auth -----------|
  |                        |--- auth_success ------>|
  |                        |                        |
  |                        |<-- join_session -------|
  |                        |--- joined_session ---->|
  |                        |                        |
  |--- send_message ------>|                        |
  |                        |--- 保存到数据库 ------->|
  |<-- new_message --------|                        |
  |                        |---- new_message ------>|
  |                        |                        |
```

## 性能优化

### 1. 进程数配置

Worker进程数建议设置为CPU核心数：

```bash
# 查看CPU核心数
cat /proc/cpuinfo | grep processor | wc -l
```

在`.env`中设置：
```ini
WEBSOCKET_WORKER_COUNT=4
```

### 2. 数据库连接池

Workerman会为每个Worker进程创建独立的数据库连接，避免连接冲突。

### 3. Redis缓存

对于高并发场景，可以使用Redis缓存会话信息：

```php
// 在ChatWebSocketServer中添加Redis支持
use think\facade\Cache;

// 缓存会话信息
Cache::set('session_' . $sessionId, $sessionData, 3600);

// 获取会话信息
$sessionData = Cache::get('session_' . $sessionId);
```

### 4. 消息队列

对于需要持久化的消息，可以使用消息队列异步处理：

```php
// 将消息推送到队列
Queue::push('SaveChatMessage', $messageData);
```

## 监控和日志

### 日志位置

Workerman的日志默认输出到标准输出。在守护进程模式下，日志会保存到：

```
backend/runtime/workerman.log
```

### 监控指标

可以监控以下指标：

- 活跃连接数
- 消息发送速率
- 错误率
- 内存使用
- CPU使用

### 日志级别

在开发环境中启用详细日志：

```php
// 在ChatWebSocketServer构造函数中添加
Worker::$logFile = runtime_path() . 'workerman.log';
Worker::$stdoutFile = runtime_path() . 'workerman_stdout.log';
```

## 故障排查

### 问题1: 端口被占用

**错误信息:**
```
stream_socket_server(): unable to bind to tcp://0.0.0.0:2346
```

**解决方法:**
```bash
# 查找占用端口的进程
lsof -i :2346

# 或使用netstat
netstat -anp | grep 2346

# 杀死占用端口的进程
kill -9 <PID>
```

### 问题2: 数据库连接失败

**错误信息:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**解决方法:**
1. 检查数据库服务是否运行
2. 检查`.env`中的数据库配置
3. 确保数据库允许远程连接

### 问题3: 消息未推送

**可能原因:**
1. 客户端未认证
2. 客户端未加入会话
3. 会话ID不正确

**调试方法:**
```bash
# 查看服务器日志
tail -f backend/runtime/workerman.log

# 在ChatWebSocketServer中添加调试日志
echo "Broadcasting to session {$sessionId}\n";
var_dump(self::$sessionConnections);
```

### 问题4: 内存泄漏

**症状:** 服务器运行一段时间后内存持续增长

**解决方法:**
1. 检查是否有未释放的连接
2. 定期清理过期的映射数据
3. 使用`memory_get_usage()`监控内存使用

```php
// 在onClose中确保清理所有引用
unset(self::$connections[$connection->id]);
unset(self::$userConnections[$userId]);
unset(self::$sessionConnections[$sessionId]);
```

## 安全性

### 1. 认证

客户端连接后必须先进行认证：

```php
// 在handleSendMessage中检查认证
if (!isset($connection->userId)) {
    $this->sendError($connection, '未认证');
    return;
}
```

### 2. 授权

确保用户只能访问自己的会话：

```php
// 验证用户是否有权限访问会话
$session = ChatSession::find($sessionId);
if ($session->user_id != $connection->userId && !$this->isAdmin($connection->userId)) {
    $this->sendError($connection, '无权限访问该会话');
    return;
}
```

### 3. 输入验证

验证所有客户端输入：

```php
// 验证消息内容
if (empty(trim($content))) {
    $this->sendError($connection, '消息内容不能为空');
    return;
}

// 限制消息长度
if (strlen($content) > 1000) {
    $this->sendError($connection, '消息内容过长');
    return;
}
```

### 4. 速率限制

防止消息轰炸：

```php
// 记录用户最后发送消息的时间
if (isset($connection->lastMessageTime)) {
    $interval = time() - $connection->lastMessageTime;
    if ($interval < 1) {
        $this->sendError($connection, '发送消息过于频繁');
        return;
    }
}
$connection->lastMessageTime = time();
```

### 5. SSL/TLS

生产环境必须使用WSS：

```ini
WEBSOCKET_SSL=true
WEBSOCKET_SSL_CERT=/path/to/cert.pem
WEBSOCKET_SSL_KEY=/path/to/key.pem
```

## 部署

### 使用Supervisor管理

创建Supervisor配置文件 `/etc/supervisor/conf.d/websocket.conf`:

```ini
[program:websocket]
command=php /path/to/backend/websocket_server.php start
directory=/path/to/backend
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/websocket.log
```

启动Supervisor：

```bash
supervisorctl reread
supervisorctl update
supervisorctl start websocket
```

### 使用Systemd管理

创建Systemd服务文件 `/etc/systemd/system/websocket.service`:

```ini
[Unit]
Description=WebSocket Server
After=network.target

[Service]
Type=forking
User=www-data
Group=www-data
WorkingDirectory=/path/to/backend
ExecStart=/usr/bin/php /path/to/backend/websocket_server.php start -d
ExecReload=/usr/bin/php /path/to/backend/websocket_server.php reload
ExecStop=/usr/bin/php /path/to/backend/websocket_server.php stop
Restart=always

[Install]
WantedBy=multi-user.target
```

启动服务：

```bash
systemctl daemon-reload
systemctl enable websocket
systemctl start websocket
systemctl status websocket
```

## 参考资料

- [Workerman官方文档](https://www.workerman.net/)
- [WebSocket协议规范](https://tools.ietf.org/html/rfc6455)
- [WebSocket API文档](./docs/WEBSOCKET_API.md)
