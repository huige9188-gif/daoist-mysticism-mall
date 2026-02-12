<?php

namespace app\websocket;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use app\service\ChatService;
use think\facade\Db;
use think\facade\Config;

/**
 * 聊天WebSocket服务器
 * 处理实时聊天消息推送
 * 验证需求: 10.2, 10.3
 */
class ChatWebSocketServer
{
    /**
     * WebSocket Worker实例
     * @var Worker
     */
    protected $worker;
    
    /**
     * 客户端连接映射 [connection_id => connection]
     * @var array
     */
    protected static $connections = [];
    
    /**
     * 用户连接映射 [user_id => [connection_id1, connection_id2, ...]]
     * @var array
     */
    protected static $userConnections = [];
    
    /**
     * 会话连接映射 [session_id => [connection_id1, connection_id2, ...]]
     * @var array
     */
    protected static $sessionConnections = [];
    
    /**
     * ChatService实例
     * @var ChatService
     */
    protected $chatService;
    
    /**
     * 构造函数
     * 
     * @param string $host WebSocket监听地址
     * @param int $port WebSocket监听端口
     */
    public function __construct(string $host = '0.0.0.0', int $port = 2346)
    {
        // 创建WebSocket Worker
        $this->worker = new Worker("websocket://{$host}:{$port}");
        
        // 设置Worker名称
        $this->worker->name = 'ChatWebSocketServer';
        
        // 设置进程数
        $this->worker->count = 4;
        
        // 初始化ChatService
        $this->chatService = new ChatService();
        
        // 设置回调函数
        $this->worker->onWorkerStart = [$this, 'onWorkerStart'];
        $this->worker->onConnect = [$this, 'onConnect'];
        $this->worker->onMessage = [$this, 'onMessage'];
        $this->worker->onClose = [$this, 'onClose'];
        $this->worker->onError = [$this, 'onError'];
    }
    
    /**
     * Worker启动时的回调
     * 
     * @param Worker $worker
     */
    public function onWorkerStart($worker)
    {
        echo "ChatWebSocketServer started on {$worker->getSocketName()}\n";
        
        // 初始化数据库连接
        $this->initDatabase();
    }
    
    /**
     * 初始化数据库连接
     */
    protected function initDatabase()
    {
        // 数据库配置已在启动脚本中初始化
        // 这里不需要额外操作
    }
    
    /**
     * 客户端连接时的回调
     * 
     * @param TcpConnection $connection
     */
    public function onConnect($connection)
    {
        echo "New connection: {$connection->id}\n";
        
        // 存储连接
        self::$connections[$connection->id] = $connection;
        
        // 发送欢迎消息
        $this->sendToConnection($connection, [
            'type' => 'connected',
            'message' => 'WebSocket连接成功',
            'connection_id' => $connection->id
        ]);
    }
    
    /**
     * 接收到消息时的回调
     * 验证需求: 10.2, 10.3
     * 
     * @param TcpConnection $connection
     * @param string $data
     */
    public function onMessage($connection, $data)
    {
        try {
            // 解析JSON消息
            $message = json_decode($data, true);
            
            if (!$message || !isset($message['type'])) {
                $this->sendError($connection, '无效的消息格式');
                return;
            }
            
            // 根据消息类型处理
            switch ($message['type']) {
                case 'auth':
                    // 认证连接
                    $this->handleAuth($connection, $message);
                    break;
                    
                case 'join_session':
                    // 加入会话
                    $this->handleJoinSession($connection, $message);
                    break;
                    
                case 'leave_session':
                    // 离开会话
                    $this->handleLeaveSession($connection, $message);
                    break;
                    
                case 'send_message':
                    // 发送消息
                    $this->handleSendMessage($connection, $message);
                    break;
                    
                case 'ping':
                    // 心跳检测
                    $this->sendToConnection($connection, ['type' => 'pong']);
                    break;
                    
                default:
                    $this->sendError($connection, '未知的消息类型');
            }
            
        } catch (\Exception $e) {
            $this->sendError($connection, $e->getMessage());
            echo "Error handling message: {$e->getMessage()}\n";
        }
    }
    
    /**
     * 处理认证
     * 
     * @param TcpConnection $connection
     * @param array $message
     */
    protected function handleAuth($connection, $message)
    {
        if (!isset($message['user_id'])) {
            $this->sendError($connection, '缺少用户ID');
            return;
        }
        
        $userId = $message['user_id'];
        
        // 存储用户ID到连接
        $connection->userId = $userId;
        
        // 添加到用户连接映射
        if (!isset(self::$userConnections[$userId])) {
            self::$userConnections[$userId] = [];
        }
        self::$userConnections[$userId][] = $connection->id;
        
        // 发送认证成功消息
        $this->sendToConnection($connection, [
            'type' => 'auth_success',
            'user_id' => $userId
        ]);
        
        echo "User {$userId} authenticated on connection {$connection->id}\n";
    }
    
    /**
     * 处理加入会话
     * 
     * @param TcpConnection $connection
     * @param array $message
     */
    protected function handleJoinSession($connection, $message)
    {
        if (!isset($message['session_id'])) {
            $this->sendError($connection, '缺少会话ID');
            return;
        }
        
        $sessionId = $message['session_id'];
        
        // 存储会话ID到连接
        $connection->sessionId = $sessionId;
        
        // 添加到会话连接映射
        if (!isset(self::$sessionConnections[$sessionId])) {
            self::$sessionConnections[$sessionId] = [];
        }
        self::$sessionConnections[$sessionId][] = $connection->id;
        
        // 发送加入成功消息
        $this->sendToConnection($connection, [
            'type' => 'joined_session',
            'session_id' => $sessionId
        ]);
        
        echo "Connection {$connection->id} joined session {$sessionId}\n";
    }
    
    /**
     * 处理离开会话
     * 
     * @param TcpConnection $connection
     * @param array $message
     */
    protected function handleLeaveSession($connection, $message)
    {
        if (!isset($connection->sessionId)) {
            return;
        }
        
        $sessionId = $connection->sessionId;
        
        // 从会话连接映射中移除
        if (isset(self::$sessionConnections[$sessionId])) {
            $key = array_search($connection->id, self::$sessionConnections[$sessionId]);
            if ($key !== false) {
                unset(self::$sessionConnections[$sessionId][$key]);
            }
            
            // 如果会话没有连接了，删除映射
            if (empty(self::$sessionConnections[$sessionId])) {
                unset(self::$sessionConnections[$sessionId]);
            }
        }
        
        // 清除连接的会话ID
        unset($connection->sessionId);
        
        // 发送离开成功消息
        $this->sendToConnection($connection, [
            'type' => 'left_session',
            'session_id' => $sessionId
        ]);
        
        echo "Connection {$connection->id} left session {$sessionId}\n";
    }
    
    /**
     * 处理发送消息
     * 验证需求: 10.2, 10.3
     * 
     * @param TcpConnection $connection
     * @param array $message
     */
    protected function handleSendMessage($connection, $message)
    {
        // 验证必需字段
        if (!isset($connection->userId)) {
            $this->sendError($connection, '未认证');
            return;
        }
        
        if (!isset($message['session_id']) || !isset($message['content'])) {
            $this->sendError($connection, '缺少会话ID或消息内容');
            return;
        }
        
        $sessionId = $message['session_id'];
        $senderId = $connection->userId;
        $content = $message['content'];
        
        try {
            // 保存消息到数据库
            $chatMessage = $this->chatService->sendMessage($sessionId, $senderId, $content);
            
            // 广播消息到会话中的所有连接
            $this->broadcastToSession($sessionId, [
                'type' => 'new_message',
                'session_id' => $sessionId,
                'message' => [
                    'id' => $chatMessage->id,
                    'session_id' => $chatMessage->session_id,
                    'sender_id' => $chatMessage->sender_id,
                    'content' => $chatMessage->content,
                    'created_at' => $chatMessage->created_at
                ]
            ]);
            
            echo "Message sent in session {$sessionId} by user {$senderId}\n";
            
        } catch (\Exception $e) {
            $this->sendError($connection, $e->getMessage());
        }
    }
    
    /**
     * 连接关闭时的回调
     * 
     * @param TcpConnection $connection
     */
    public function onClose($connection)
    {
        echo "Connection closed: {$connection->id}\n";
        
        // 从用户连接映射中移除
        if (isset($connection->userId)) {
            $userId = $connection->userId;
            if (isset(self::$userConnections[$userId])) {
                $key = array_search($connection->id, self::$userConnections[$userId]);
                if ($key !== false) {
                    unset(self::$userConnections[$userId][$key]);
                }
                
                // 如果用户没有连接了，删除映射
                if (empty(self::$userConnections[$userId])) {
                    unset(self::$userConnections[$userId]);
                }
            }
        }
        
        // 从会话连接映射中移除
        if (isset($connection->sessionId)) {
            $sessionId = $connection->sessionId;
            if (isset(self::$sessionConnections[$sessionId])) {
                $key = array_search($connection->id, self::$sessionConnections[$sessionId]);
                if ($key !== false) {
                    unset(self::$sessionConnections[$sessionId][$key]);
                }
                
                // 如果会话没有连接了，删除映射
                if (empty(self::$sessionConnections[$sessionId])) {
                    unset(self::$sessionConnections[$sessionId]);
                }
            }
        }
        
        // 从连接映射中移除
        unset(self::$connections[$connection->id]);
    }
    
    /**
     * 错误时的回调
     * 
     * @param TcpConnection $connection
     * @param int $code
     * @param string $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "Error on connection {$connection->id}: [{$code}] {$msg}\n";
    }
    
    /**
     * 发送消息到指定连接
     * 
     * @param TcpConnection $connection
     * @param array $data
     */
    protected function sendToConnection($connection, array $data)
    {
        $connection->send(json_encode($data));
    }
    
    /**
     * 发送错误消息到连接
     * 
     * @param TcpConnection $connection
     * @param string $error
     */
    protected function sendError($connection, string $error)
    {
        $this->sendToConnection($connection, [
            'type' => 'error',
            'error' => $error
        ]);
    }
    
    /**
     * 广播消息到会话中的所有连接
     * 验证需求: 10.2, 10.3
     * 
     * @param int $sessionId 会话ID
     * @param array $data 消息数据
     */
    public function broadcastToSession(int $sessionId, array $data)
    {
        if (!isset(self::$sessionConnections[$sessionId])) {
            return;
        }
        
        $connectionIds = self::$sessionConnections[$sessionId];
        
        foreach ($connectionIds as $connectionId) {
            if (isset(self::$connections[$connectionId])) {
                $this->sendToConnection(self::$connections[$connectionId], $data);
            }
        }
    }
    
    /**
     * 广播消息到指定用户的所有连接
     * 
     * @param int $userId 用户ID
     * @param array $data 消息数据
     */
    public function broadcastToUser(int $userId, array $data)
    {
        if (!isset(self::$userConnections[$userId])) {
            return;
        }
        
        $connectionIds = self::$userConnections[$userId];
        
        foreach ($connectionIds as $connectionId) {
            if (isset(self::$connections[$connectionId])) {
                $this->sendToConnection(self::$connections[$connectionId], $data);
            }
        }
    }
    
    /**
     * 启动WebSocket服务器
     */
    public function start()
    {
        Worker::runAll();
    }
}
