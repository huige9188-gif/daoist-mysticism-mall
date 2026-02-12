# WebSocket API 文档

## 概述

WebSocket服务器用于实时聊天功能，支持客服与用户之间的实时消息推送。

**服务器地址:** `ws://localhost:2346`

**验证需求:** 10.2, 10.3

## 连接

客户端通过WebSocket协议连接到服务器：

```javascript
const ws = new WebSocket('ws://localhost:2346');

ws.onopen = function() {
    console.log('WebSocket连接成功');
};

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('收到消息:', data);
};

ws.onerror = function(error) {
    console.error('WebSocket错误:', error);
};

ws.onclose = function() {
    console.log('WebSocket连接关闭');
};
```

## 消息格式

所有消息使用JSON格式，包含`type`字段标识消息类型。

### 客户端发送消息格式

```json
{
    "type": "消息类型",
    "其他字段": "根据消息类型而定"
}
```

### 服务器响应消息格式

```json
{
    "type": "消息类型",
    "其他字段": "根据消息类型而定"
}
```

## 消息类型

### 1. 连接成功 (connected)

**方向:** 服务器 → 客户端

**触发时机:** 客户端连接成功时

**消息格式:**
```json
{
    "type": "connected",
    "message": "WebSocket连接成功",
    "connection_id": "连接ID"
}
```

### 2. 认证 (auth)

**方向:** 客户端 → 服务器

**说明:** 客户端连接后需要先进行认证，绑定用户ID

**请求格式:**
```json
{
    "type": "auth",
    "user_id": 123
}
```

**响应格式:**
```json
{
    "type": "auth_success",
    "user_id": 123
}
```

**错误响应:**
```json
{
    "type": "error",
    "error": "缺少用户ID"
}
```

### 3. 加入会话 (join_session)

**方向:** 客户端 → 服务器

**说明:** 加入指定的聊天会话，开始接收该会话的消息

**请求格式:**
```json
{
    "type": "join_session",
    "session_id": 456
}
```

**响应格式:**
```json
{
    "type": "joined_session",
    "session_id": 456
}
```

**错误响应:**
```json
{
    "type": "error",
    "error": "缺少会话ID"
}
```

### 4. 离开会话 (leave_session)

**方向:** 客户端 → 服务器

**说明:** 离开当前会话，停止接收该会话的消息

**请求格式:**
```json
{
    "type": "leave_session"
}
```

**响应格式:**
```json
{
    "type": "left_session",
    "session_id": 456
}
```

### 5. 发送消息 (send_message)

**方向:** 客户端 → 服务器

**说明:** 在会话中发送消息

**验证需求:** 10.2, 10.3

**请求格式:**
```json
{
    "type": "send_message",
    "session_id": 456,
    "content": "消息内容"
}
```

**错误响应:**
```json
{
    "type": "error",
    "error": "未认证"
}
```

或

```json
{
    "type": "error",
    "error": "缺少会话ID或消息内容"
}
```

### 6. 新消息通知 (new_message)

**方向:** 服务器 → 客户端

**说明:** 会话中有新消息时，服务器推送给所有在该会话中的客户端

**验证需求:** 10.2, 10.3

**消息格式:**
```json
{
    "type": "new_message",
    "session_id": 456,
    "message": {
        "id": 789,
        "session_id": 456,
        "sender_id": 123,
        "content": "消息内容",
        "created_at": "2024-01-01 12:00:00"
    }
}
```

### 7. 心跳检测 (ping/pong)

**方向:** 客户端 → 服务器 → 客户端

**说明:** 保持连接活跃，防止超时断开

**请求格式:**
```json
{
    "type": "ping"
}
```

**响应格式:**
```json
{
    "type": "pong"
}
```

### 8. 错误消息 (error)

**方向:** 服务器 → 客户端

**说明:** 服务器处理消息时发生错误

**消息格式:**
```json
{
    "type": "error",
    "error": "错误描述"
}
```

## 使用流程

### 用户发起聊天

1. 客户端连接WebSocket服务器
2. 收到`connected`消息
3. 发送`auth`消息进行认证
4. 收到`auth_success`消息
5. 通过HTTP API创建聊天会话，获得`session_id`
6. 发送`join_session`消息加入会话
7. 收到`joined_session`消息
8. 发送`send_message`消息发送聊天内容
9. 收到`new_message`消息（自己发送的消息也会收到）

### 客服接收聊天

1. 客户端连接WebSocket服务器
2. 收到`connected`消息
3. 发送`auth`消息进行认证（使用客服的user_id）
4. 收到`auth_success`消息
5. 通过HTTP API获取活跃会话列表
6. 选择一个会话，发送`join_session`消息
7. 收到`joined_session`消息
8. 收到`new_message`消息（用户发送的消息）
9. 发送`send_message`消息回复用户
10. 收到`new_message`消息（自己发送的消息也会收到）

## 完整示例

### JavaScript客户端示例

```javascript
class ChatWebSocketClient {
    constructor(url, userId) {
        this.url = url;
        this.userId = userId;
        this.ws = null;
        this.sessionId = null;
    }
    
    connect() {
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(this.url);
            
            this.ws.onopen = () => {
                console.log('WebSocket连接成功');
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
                
                if (data.type === 'connected') {
                    // 连接成功后自动认证
                    this.authenticate();
                }
                
                if (data.type === 'auth_success') {
                    resolve();
                }
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket错误:', error);
                reject(error);
            };
            
            this.ws.onclose = () => {
                console.log('WebSocket连接关闭');
            };
        });
    }
    
    authenticate() {
        this.send({
            type: 'auth',
            user_id: this.userId
        });
    }
    
    joinSession(sessionId) {
        this.sessionId = sessionId;
        this.send({
            type: 'join_session',
            session_id: sessionId
        });
    }
    
    leaveSession() {
        if (this.sessionId) {
            this.send({
                type: 'leave_session'
            });
            this.sessionId = null;
        }
    }
    
    sendMessage(content) {
        if (!this.sessionId) {
            console.error('未加入会话');
            return;
        }
        
        this.send({
            type: 'send_message',
            session_id: this.sessionId,
            content: content
        });
    }
    
    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.error('WebSocket未连接');
        }
    }
    
    handleMessage(data) {
        console.log('收到消息:', data);
        
        switch (data.type) {
            case 'new_message':
                this.onNewMessage(data.message);
                break;
            case 'error':
                this.onError(data.error);
                break;
        }
    }
    
    onNewMessage(message) {
        // 处理新消息
        console.log('新消息:', message);
    }
    
    onError(error) {
        // 处理错误
        console.error('错误:', error);
    }
    
    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
    
    // 心跳检测
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            this.send({ type: 'ping' });
        }, 30000); // 每30秒发送一次心跳
    }
    
    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
        }
    }
}

// 使用示例
const client = new ChatWebSocketClient('ws://localhost:2346', 123);

client.connect().then(() => {
    console.log('认证成功');
    
    // 加入会话
    client.joinSession(456);
    
    // 发送消息
    setTimeout(() => {
        client.sendMessage('你好，我需要咨询');
    }, 1000);
    
    // 启动心跳
    client.startHeartbeat();
});
```

## 服务器管理

### 启动服务器

```bash
# 前台运行（用于开发调试）
php websocket_server.php start

# 后台运行（用于生产环境）
php websocket_server.php start -d
```

### 停止服务器

```bash
php websocket_server.php stop
```

### 重启服务器

```bash
php websocket_server.php restart
```

### 查看服务器状态

```bash
php websocket_server.php status
```

## 性能和扩展

### 进程数配置

在`.env`文件中配置Worker进程数：

```
WEBSOCKET_WORKER_COUNT=4
```

建议设置为CPU核心数。

### 连接数限制

单个Worker进程可以处理数千个并发连接。如果需要支持更多连接，可以增加Worker进程数。

### 负载均衡

对于大规模部署，可以使用Nginx进行WebSocket负载均衡：

```nginx
upstream websocket {
    server 127.0.0.1:2346;
    server 127.0.0.1:2347;
    server 127.0.0.1:2348;
}

server {
    listen 80;
    server_name ws.example.com;
    
    location / {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

## 安全性

### SSL/TLS支持

在生产环境中，建议使用WSS（WebSocket Secure）协议：

1. 在`.env`文件中配置SSL：

```
WEBSOCKET_SSL=true
WEBSOCKET_SSL_CERT=/path/to/cert.pem
WEBSOCKET_SSL_KEY=/path/to/key.pem
```

2. 客户端使用`wss://`协议连接：

```javascript
const ws = new WebSocket('wss://ws.example.com');
```

### 认证和授权

- 客户端连接后必须先进行认证
- 可以在认证时验证JWT令牌
- 确保用户只能访问自己的会话

## 故障排查

### 连接失败

1. 检查服务器是否启动：`php websocket_server.php status`
2. 检查端口是否被占用：`netstat -an | grep 2346`
3. 检查防火墙设置

### 消息未推送

1. 检查客户端是否已认证
2. 检查客户端是否已加入会话
3. 查看服务器日志

### 性能问题

1. 增加Worker进程数
2. 优化数据库查询
3. 使用Redis缓存会话信息
