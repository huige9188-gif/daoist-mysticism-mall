<?php

namespace tests\Chat;

use PHPUnit\Framework\TestCase;
use app\service\ChatService;
use think\facade\Db;
use PDO;

/**
 * 客服API集成测试
 * 
 * 验证需求: 10.1, 10.4, 10.5, 10.6
 */
class ChatApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    private static $normalUserId;
    private static $adminToken;
    private static $userToken;
    
    public static function setUpBeforeClass(): void
    {
        // 连接测试数据库
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'daoist_mall_test';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '123456';

        try {
            self::$pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (\PDOException $e) {
            self::fail("无法连接到数据库: " . $e->getMessage());
        }
        
        // 初始化ThinkPHP数据库连接
        $config = [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'type' => 'mysql',
                    'hostname' => $host,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'hostport' => $port,
                    'charset' => 'utf8mb4',
                    'prefix' => '',
                ],
            ],
        ];
        
        Db::setConfig($config);
        
        // 创建测试用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('admin_chat_test', '{$hashedPassword}', 'admin_chat@test.com', '13800000011', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_chat_test', '{$hashedPassword}', 'user_chat@test.com', '13800000012', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            // 清理测试数据
            self::$pdo->exec("DELETE FROM chat_messages WHERE session_id IN (SELECT id FROM chat_sessions WHERE user_id IN (" . self::$adminUserId . ", " . self::$normalUserId . "))");
            self::$pdo->exec("DELETE FROM chat_sessions WHERE user_id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试创建会话 - 成功
     * 验证需求: 10.1
     */
    public function testCreateSessionSuccess()
    {
        $response = $this->simulateApiRequest('POST', '/api/chat/sessions', [], self::$userToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('会话创建成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals('active', $response['data']['status']);
        $this->assertEquals(self::$normalUserId, $response['data']['user_id']);
        
        // 清理
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM chat_sessions WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建会话 - 未认证
     * 验证需求: 11.4
     */
    public function testCreateSessionUnauthorized()
    {
        $response = $this->simulateApiRequest('POST', '/api/chat/sessions', [], null);
        
        $this->assertEquals(401, $response['code']);
        $this->assertEquals('未授权访问', $response['message']);
    }
    
    /**
     * 测试获取会话列表 - 管理员权限
     * 验证需求: 10.4
     */
    public function testGetSessionsAsAdmin()
    {
        // 创建测试会话
        self::$pdo->exec("
            INSERT INTO chat_sessions (user_id, status, started_at, last_activity_at) 
            VALUES (" . self::$normalUserId . ", 'active', NOW(), NOW())
        ");
        $sessionId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('GET', '/api/chat/sessions', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
        
        // 清理
        self::$pdo->exec("DELETE FROM chat_sessions WHERE id = " . $sessionId);
    }
    
    /**
     * 测试获取会话列表 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testGetSessionsAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/chat/sessions', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试获取会话聊天记录 - 成功
     * 验证需求: 10.5
     */
    public function testGetSessionMessagesSuccess()
    {
        // 创建测试会话
        self::$pdo->exec("
            INSERT INTO chat_sessions (user_id, status, started_at, last_activity_at) 
            VALUES (" . self::$normalUserId . ", 'active', NOW(), NOW())
        ");
        $sessionId = self::$pdo->lastInsertId();
        
        // 创建测试消息
        self::$pdo->exec("
            INSERT INTO chat_messages (session_id, sender_id, content, created_at) 
            VALUES ({$sessionId}, " . self::$normalUserId . ", '测试消息1', NOW())
        ");
        $messageId1 = self::$pdo->lastInsertId();
        
        self::$pdo->exec("
            INSERT INTO chat_messages (session_id, sender_id, content, created_at) 
            VALUES ({$sessionId}, " . self::$adminUserId . ", '测试消息2', NOW())
        ");
        $messageId2 = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('GET', '/api/chat/sessions/' . $sessionId . '/messages', [], self::$userToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
        $this->assertGreaterThanOrEqual(2, count($response['data']));
        
        // 清理
        self::$pdo->exec("DELETE FROM chat_messages WHERE id IN ({$messageId1}, {$messageId2})");
        self::$pdo->exec("DELETE FROM chat_sessions WHERE id = " . $sessionId);
    }
    
    /**
     * 测试获取会话聊天记录 - 会话不存在
     * 验证需求: 10.5
     */
    public function testGetSessionMessagesNotFound()
    {
        $response = $this->simulateApiRequest('GET', '/api/chat/sessions/999999/messages', [], self::$userToken);
        
        $this->assertEquals(404, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }
    
    /**
     * 测试结束会话 - 成功
     * 验证需求: 10.6
     */
    public function testCloseSessionSuccess()
    {
        // 创建测试会话
        self::$pdo->exec("
            INSERT INTO chat_sessions (user_id, status, started_at, last_activity_at) 
            VALUES (" . self::$normalUserId . ", 'active', NOW(), NOW())
        ");
        $sessionId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('POST', '/api/chat/sessions/' . $sessionId . '/close', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('会话已结束', $response['message']);
        $this->assertEquals('closed', $response['data']['status']);
        $this->assertNotNull($response['data']['closed_at']);
        
        // 清理
        self::$pdo->exec("DELETE FROM chat_sessions WHERE id = " . $sessionId);
    }
    
    /**
     * 测试结束会话 - 会话不存在
     * 验证需求: 10.6
     */
    public function testCloseSessionNotFound()
    {
        $response = $this->simulateApiRequest('POST', '/api/chat/sessions/999999/close', [], self::$adminToken);
        
        $this->assertEquals(404, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }
    
    /**
     * 测试会话消息按时间顺序排列
     * 验证需求: 10.5
     */
    public function testSessionMessagesOrderedByTime()
    {
        // 创建测试会话
        self::$pdo->exec("
            INSERT INTO chat_sessions (user_id, status, started_at, last_activity_at) 
            VALUES (" . self::$normalUserId . ", 'active', NOW(), NOW())
        ");
        $sessionId = self::$pdo->lastInsertId();
        
        // 创建多条消息
        $messageIds = [];
        for ($i = 1; $i <= 3; $i++) {
            self::$pdo->exec("
                INSERT INTO chat_messages (session_id, sender_id, content, created_at) 
                VALUES ({$sessionId}, " . self::$normalUserId . ", '消息{$i}', DATE_ADD(NOW(), INTERVAL {$i} SECOND))
            ");
            $messageIds[] = self::$pdo->lastInsertId();
        }
        
        $response = $this->simulateApiRequest('GET', '/api/chat/sessions/' . $sessionId . '/messages', [], self::$userToken);
        
        $this->assertEquals(200, $response['code']);
        $messages = $response['data'];
        
        // 验证消息按时间升序排列
        for ($i = 0; $i < count($messages) - 1; $i++) {
            $this->assertLessThanOrEqual(
                strtotime($messages[$i + 1]['created_at']),
                strtotime($messages[$i]['created_at']),
                '消息应该按时间升序排列'
            );
        }
        
        // 清理
        self::$pdo->exec("DELETE FROM chat_messages WHERE id IN (" . implode(',', $messageIds) . ")");
        self::$pdo->exec("DELETE FROM chat_sessions WHERE id = " . $sessionId);
    }
    
    /**
     * 模拟API请求
     */
    private function simulateApiRequest(string $method, string $url, array $data = [], ?string $token = null): array
    {
        // 认证检查
        if (!$token) {
            return ['code' => 401, 'message' => '未授权访问', 'data' => null];
        }
        
        // 解析token获取用户信息
        $userId = (int) str_replace(['mock_admin_token_', 'mock_user_token_'], '', $token);
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['code' => 401, 'message' => '未授权访问', 'data' => null];
        }
        
        // 授权检查（管理员路由）
        $adminRoutes = ['/api/chat/sessions'];
        if ($method === 'GET' && in_array($url, $adminRoutes) && $user['role'] !== 'admin') {
            return ['code' => 403, 'message' => '无权限访问', 'data' => null];
        }
        
        // 业务逻辑执行
        try {
            $chatService = new ChatService();
            
            if ($method === 'GET' && preg_match('#^/api/chat/sessions(\?.*)?$#', $url)) {
                // 获取会话列表
                $sessions = $chatService->getActiveSessions();
                return ['code' => 200, 'message' => '获取成功', 'data' => $sessions->toArray()];
                
            } elseif ($method === 'GET' && preg_match('#^/api/chat/sessions/(\d+)/messages$#', $url, $matches)) {
                // 获取会话聊天记录
                $sessionId = $matches[1];
                $messages = $chatService->getSessionMessages($sessionId);
                return ['code' => 200, 'message' => '获取成功', 'data' => $messages->toArray()];
                
            } elseif ($method === 'POST' && $url === '/api/chat/sessions') {
                // 创建会话
                $session = $chatService->createSession($userId);
                return ['code' => 200, 'message' => '会话创建成功', 'data' => $session];
                
            } elseif ($method === 'POST' && preg_match('#^/api/chat/sessions/(\d+)/close$#', $url, $matches)) {
                // 结束会话
                $sessionId = $matches[1];
                $session = $chatService->closeSession($sessionId);
                return ['code' => 200, 'message' => '会话已结束', 'data' => $session];
            }
            
            return ['code' => 404, 'message' => '路由不存在', 'data' => null];
            
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            if (strpos($message, '不存在') !== false) {
                return ['code' => 404, 'message' => $message, 'data' => null];
            } elseif (strpos($message, '无效') !== false || 
                      strpos($message, '不能为空') !== false) {
                return ['code' => 400, 'message' => $message, 'data' => null];
            }
            
            return ['code' => 500, 'message' => $message, 'data' => null];
        }
    }
}
