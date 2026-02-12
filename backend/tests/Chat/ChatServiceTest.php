<?php

namespace Tests\Chat;

use PHPUnit\Framework\TestCase;
use app\model\ChatSession;
use app\model\ChatMessage;
use app\model\User;
use app\service\ChatService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 客服服务测试
 * 
 * 验证需求: 10.1, 10.6, 10.7
 */
class ChatServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private ChatService $service;
    private ?User $testUser = null;
    private ?User $testAdmin = null;
    
    public static function setUpBeforeClass(): void
    {
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
    }
    
    protected function setUp(): void
    {
        $this->service = new ChatService();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空相关表
        self::$pdo->exec("TRUNCATE TABLE chat_messages");
        self::$pdo->exec("TRUNCATE TABLE chat_sessions");
        self::$pdo->exec("TRUNCATE TABLE users");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // 创建测试用户
        $this->testUser = User::create([
            'username' => 'testuser',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'email' => 'test@example.com',
            'phone' => '13800138000',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 创建测试管理员
        $this->testAdmin = User::create([
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'email' => 'admin@example.com',
            'phone' => '13800138001',
            'role' => 'admin',
            'status' => 1
        ]);
    }
    
    /**
     * 测试创建会话成功
     * 验证需求: 10.1
     */
    public function testCreateSessionSuccess(): void
    {
        $session = $this->service->createSession($this->testUser->id);
        
        // 验证会话创建成功
        $this->assertInstanceOf(ChatSession::class, $session);
        $this->assertEquals($this->testUser->id, $session->user_id);
        $this->assertEquals(ChatSession::STATUS_ACTIVE, $session->status);
        $this->assertNotNull($session->started_at);
        $this->assertNotNull($session->last_activity_at);
    }
    
    /**
     * 测试创建会话时用户ID无效
     * 验证需求: 10.1
     */
    public function testCreateSessionWithInvalidUserId(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('用户ID无效');
        
        $this->service->createSession(0);
    }
    
    /**
     * 测试发送消息成功
     * 验证需求: 10.2, 10.3
     */
    public function testSendMessageSuccess(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        // 记录会话的初始最后活跃时间
        $initialLastActivity = $session->last_activity_at;
        
        // 等待1秒以确保时间戳不同
        sleep(1);
        
        // 发送消息
        $content = '你好，我想咨询一下商品信息';
        $message = $this->service->sendMessage($session->id, $this->testUser->id, $content);
        
        // 验证消息创建成功
        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals($session->id, $message->session_id);
        $this->assertEquals($this->testUser->id, $message->sender_id);
        $this->assertEquals($content, $message->content);
        $this->assertNotNull($message->created_at);
        
        // 验证会话最后活跃时间已更新
        $session->refresh();
        $this->assertGreaterThan($initialLastActivity, $session->last_activity_at);
    }
    
    /**
     * 测试发送消息时会话不存在
     * 验证需求: 10.2
     */
    public function testSendMessageWithNonExistentSession(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('会话不存在');
        
        $this->service->sendMessage(99999, $this->testUser->id, '测试消息');
    }
    
    /**
     * 测试发送空消息
     * 验证需求: 10.2
     */
    public function testSendEmptyMessage(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('消息内容不能为空');
        
        $this->service->sendMessage($session->id, $this->testUser->id, '');
    }
    
    /**
     * 测试发送只包含空白字符的消息
     * 验证需求: 10.2
     */
    public function testSendWhitespaceOnlyMessage(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('消息内容不能为空');
        
        $this->service->sendMessage($session->id, $this->testUser->id, '   ');
    }
    
    /**
     * 测试结束会话成功
     * 验证需求: 10.6
     */
    public function testCloseSessionSuccess(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        // 结束会话
        $closedSession = $this->service->closeSession($session->id);
        
        // 验证会话状态
        $this->assertEquals(ChatSession::STATUS_CLOSED, $closedSession->status);
        $this->assertNotNull($closedSession->closed_at);
    }
    
    /**
     * 测试结束不存在的会话
     * 验证需求: 10.6
     */
    public function testCloseNonExistentSession(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('会话不存在');
        
        $this->service->closeSession(99999);
    }
    
    /**
     * 测试检查不活跃会话
     * 验证需求: 10.7
     */
    public function testCheckInactiveSessions(): void
    {
        // 创建多个会话
        $session1 = $this->service->createSession($this->testUser->id);
        $session2 = $this->service->createSession($this->testUser->id);
        $session3 = $this->service->createSession($this->testUser->id);
        
        // 设置session1和session2的最后活跃时间为31分钟前
        $inactiveTime = date('Y-m-d H:i:s', strtotime('-31 minutes'));
        $session1->last_activity_at = $inactiveTime;
        $session1->save();
        
        $session2->last_activity_at = $inactiveTime;
        $session2->save();
        
        // session3保持活跃（刚创建）
        
        // 执行检查
        $count = $this->service->checkInactiveSessions();
        
        // 验证有2个会话被标记为不活跃
        $this->assertEquals(2, $count);
        
        // 验证会话状态
        $session1->refresh();
        $session2->refresh();
        $session3->refresh();
        
        $this->assertEquals(ChatSession::STATUS_INACTIVE, $session1->status);
        $this->assertEquals(ChatSession::STATUS_INACTIVE, $session2->status);
        $this->assertEquals(ChatSession::STATUS_ACTIVE, $session3->status);
    }
    
    /**
     * 测试检查不活跃会话时没有超时会话
     * 验证需求: 10.7
     */
    public function testCheckInactiveSessionsWithNoTimeoutSessions(): void
    {
        // 创建活跃会话
        $this->service->createSession($this->testUser->id);
        $this->service->createSession($this->testUser->id);
        
        // 执行检查
        $count = $this->service->checkInactiveSessions();
        
        // 验证没有会话被标记为不活跃
        $this->assertEquals(0, $count);
    }
    
    /**
     * 测试检查不活跃会话时恰好30分钟
     * 验证需求: 10.7
     */
    public function testCheckInactiveSessionsExactly30Minutes(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        // 设置最后活跃时间为恰好30分钟前
        $exactTime = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        $session->last_activity_at = $exactTime;
        $session->save();
        
        // 执行检查
        $count = $this->service->checkInactiveSessions();
        
        // 验证会话仍然活跃（需要超过30分钟）
        $this->assertEquals(0, $count);
        
        $session->refresh();
        $this->assertEquals(ChatSession::STATUS_ACTIVE, $session->status);
    }
    
    /**
     * 测试获取活跃会话列表
     * 验证需求: 10.4
     */
    public function testGetActiveSessions(): void
    {
        // 创建多个会话
        $session1 = $this->service->createSession($this->testUser->id);
        $session2 = $this->service->createSession($this->testUser->id);
        $session3 = $this->service->createSession($this->testUser->id);
        
        // 关闭一个会话
        $this->service->closeSession($session3->id);
        
        // 设置一个会话为不活跃
        $session2->status = ChatSession::STATUS_INACTIVE;
        $session2->save();
        
        // 获取活跃会话列表
        $activeSessions = $this->service->getActiveSessions();
        
        // 验证只返回活跃会话
        $this->assertCount(1, $activeSessions);
        $this->assertEquals($session1->id, $activeSessions[0]->id);
        $this->assertEquals(ChatSession::STATUS_ACTIVE, $activeSessions[0]->status);
    }
    
    /**
     * 测试获取会话聊天记录
     * 验证需求: 10.5
     */
    public function testGetSessionMessages(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        // 发送多条消息
        $this->service->sendMessage($session->id, $this->testUser->id, '第一条消息');
        sleep(1);
        $this->service->sendMessage($session->id, $this->testAdmin->id, '第二条消息');
        sleep(1);
        $this->service->sendMessage($session->id, $this->testUser->id, '第三条消息');
        
        // 获取聊天记录
        $messages = $this->service->getSessionMessages($session->id);
        
        // 验证消息数量和顺序
        $this->assertCount(3, $messages);
        $this->assertEquals('第一条消息', $messages[0]->content);
        $this->assertEquals('第二条消息', $messages[1]->content);
        $this->assertEquals('第三条消息', $messages[2]->content);
        
        // 验证消息按时间升序排列
        $this->assertLessThanOrEqual($messages[1]->created_at, $messages[0]->created_at);
        $this->assertLessThanOrEqual($messages[2]->created_at, $messages[1]->created_at);
    }
    
    /**
     * 测试获取不存在会话的聊天记录
     * 验证需求: 10.5
     */
    public function testGetSessionMessagesWithNonExistentSession(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('会话不存在');
        
        $this->service->getSessionMessages(99999);
    }
    
    /**
     * 测试获取空会话的聊天记录
     * 验证需求: 10.5
     */
    public function testGetSessionMessagesWithEmptySession(): void
    {
        // 创建会话但不发送消息
        $session = $this->service->createSession($this->testUser->id);
        
        // 获取聊天记录
        $messages = $this->service->getSessionMessages($session->id);
        
        // 验证返回空列表
        $this->assertCount(0, $messages);
    }
    
    /**
     * 测试根据ID获取会话
     */
    public function testGetSessionById(): void
    {
        // 创建会话
        $session = $this->service->createSession($this->testUser->id);
        
        // 发送消息
        $this->service->sendMessage($session->id, $this->testUser->id, '测试消息');
        
        // 获取会话详情
        $fetchedSession = $this->service->getSessionById($session->id);
        
        $this->assertNotNull($fetchedSession);
        $this->assertEquals($session->id, $fetchedSession->id);
        $this->assertNotNull($fetchedSession->user);
        $this->assertNotNull($fetchedSession->messages);
    }
    
    /**
     * 测试获取不存在的会话
     */
    public function testGetSessionByIdNotFound(): void
    {
        $session = $this->service->getSessionById(99999);
        
        $this->assertNull($session);
    }
    
    /**
     * 测试完整的聊天流程
     * 验证需求: 10.1, 10.2, 10.3, 10.6
     */
    public function testCompleteChatFlow(): void
    {
        // 1. 用户创建会话
        $session = $this->service->createSession($this->testUser->id);
        $this->assertEquals(ChatSession::STATUS_ACTIVE, $session->status);
        
        // 2. 用户发送消息
        $userMessage = $this->service->sendMessage($session->id, $this->testUser->id, '你好，我想咨询商品');
        $this->assertEquals($this->testUser->id, $userMessage->sender_id);
        
        // 3. 客服回复
        $adminMessage = $this->service->sendMessage($session->id, $this->testAdmin->id, '您好，请问有什么可以帮您？');
        $this->assertEquals($this->testAdmin->id, $adminMessage->sender_id);
        
        // 4. 用户继续提问
        $this->service->sendMessage($session->id, $this->testUser->id, '这个商品有什么功效？');
        
        // 5. 客服回答
        $this->service->sendMessage($session->id, $this->testAdmin->id, '这个商品具有招财辟邪的功效');
        
        // 6. 获取聊天记录
        $messages = $this->service->getSessionMessages($session->id);
        $this->assertCount(4, $messages);
        
        // 7. 结束会话
        $closedSession = $this->service->closeSession($session->id);
        $this->assertEquals(ChatSession::STATUS_CLOSED, $closedSession->status);
    }
}
