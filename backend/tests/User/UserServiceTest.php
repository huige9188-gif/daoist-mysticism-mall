<?php

namespace Tests\User;

use PHPUnit\Framework\TestCase;
use app\model\User;
use app\service\UserService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 用户服务测试
 * 
 * 验证需求: 2.1, 2.2, 2.3, 2.4, 2.6
 */
class UserServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private UserService $service;
    
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
        $this->service = new UserService();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空用户表
        self::$pdo->exec("TRUNCATE TABLE users");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * 测试创建用户成功
     * 验证需求: 2.2
     */
    public function testCreateUserSuccess(): void
    {
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'phone' => '13800138000',
            'role' => 'user',
            'status' => 1
        ];
        
        $user = $this->service->createUser($data);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNotEquals('password123', $user->password); // 密码应该被加密
    }
    
    /**
     * 测试创建用户时用户名重复
     * 验证需求: 2.2
     */
    public function testCreateUserWithDuplicateUsername(): void
    {
        // 创建第一个用户
        $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test1@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 尝试创建相同用户名的用户
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('用户名已存在');
        
        $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password456',
            'email' => 'test2@example.com',
            'role' => 'user',
            'status' => 1
        ]);
    }
    
    /**
     * 测试创建用户时邮箱格式错误
     * 验证需求: 2.2
     */
    public function testCreateUserWithInvalidEmail(): void
    {
        $this->expectException(ValidateException::class);
        
        $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'invalid-email',
            'role' => 'user',
            'status' => 1
        ]);
    }
    
    /**
     * 测试更新用户成功
     * 验证需求: 2.3
     */
    public function testUpdateUserSuccess(): void
    {
        // 创建用户
        $user = $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 更新用户
        $updatedUser = $this->service->updateUser($user->id, [
            'phone' => '13900139000',
            'status' => 0
        ]);
        
        $this->assertEquals('13900139000', $updatedUser->phone);
        $this->assertEquals(0, $updatedUser->status);
    }
    
    /**
     * 测试更新不存在的用户
     * 验证需求: 2.3
     */
    public function testUpdateNonExistentUser(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('用户不存在');
        
        $this->service->updateUser(99999, [
            'phone' => '13900139000'
        ]);
    }
    
    /**
     * 测试更新用户时用户名重复
     * 验证需求: 2.3
     */
    public function testUpdateUserWithDuplicateUsername(): void
    {
        // 创建两个用户
        $user1 = $this->service->createUser([
            'username' => 'user1',
            'password' => 'password123',
            'email' => 'user1@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        $user2 = $this->service->createUser([
            'username' => 'user2',
            'password' => 'password123',
            'email' => 'user2@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 尝试将user2的用户名改为user1
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('用户名已存在');
        
        $this->service->updateUser($user2->id, [
            'username' => 'user1'
        ]);
    }
    
    /**
     * 测试更新用户时邮箱重复
     * 验证需求: 2.3
     */
    public function testUpdateUserWithDuplicateEmail(): void
    {
        // 创建两个用户
        $user1 = $this->service->createUser([
            'username' => 'user1',
            'password' => 'password123',
            'email' => 'user1@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        $user2 = $this->service->createUser([
            'username' => 'user2',
            'password' => 'password123',
            'email' => 'user2@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 尝试将user2的邮箱改为user1的邮箱
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('邮箱已存在');
        
        $this->service->updateUser($user2->id, [
            'email' => 'user1@example.com'
        ]);
    }
    
    /**
     * 测试删除用户（软删除）
     * 验证需求: 2.4
     */
    public function testDeleteUser(): void
    {
        // 创建用户
        $user = $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 删除用户
        $result = $this->service->deleteUser($user->id);
        $this->assertTrue($result);
        
        // 验证用户仍然存在于数据库中（软删除）
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $user->id]);
        $deletedUser = $stmt->fetch();
        
        $this->assertNotNull($deletedUser);
        $this->assertNotNull($deletedUser['deleted_at']);
    }
    
    /**
     * 测试删除不存在的用户
     * 验证需求: 2.4
     */
    public function testDeleteNonExistentUser(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('用户不存在');
        
        $this->service->deleteUser(99999);
    }
    
    /**
     * 测试获取用户列表
     * 验证需求: 2.1
     */
    public function testGetUserList(): void
    {
        // 创建多个用户
        for ($i = 1; $i <= 15; $i++) {
            $this->service->createUser([
                'username' => "user{$i}",
                'password' => 'password123',
                'email' => "user{$i}@example.com",
                'role' => 'user',
                'status' => 1
            ]);
        }
        
        // 获取第一页（每页10条）
        $result = $this->service->getUserList(1, 10);
        
        $this->assertCount(10, $result->items());
        $this->assertEquals(15, $result->total());
    }
    
    /**
     * 测试搜索用户（按用户名）
     * 验证需求: 2.6
     */
    public function testSearchUsersByUsername(): void
    {
        // 创建用户
        $this->service->createUser([
            'username' => 'alice',
            'password' => 'password123',
            'email' => 'alice@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        $this->service->createUser([
            'username' => 'bob',
            'password' => 'password123',
            'email' => 'bob@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        $this->service->createUser([
            'username' => 'charlie',
            'password' => 'password123',
            'email' => 'charlie@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 搜索包含"ali"的用户
        $result = $this->service->getUserList(1, 10, 'ali');
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('alice', $result->items()[0]->username);
    }
    
    /**
     * 测试搜索用户（按邮箱）
     * 验证需求: 2.6
     */
    public function testSearchUsersByEmail(): void
    {
        // 创建用户
        $this->service->createUser([
            'username' => 'user1',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        $this->service->createUser([
            'username' => 'user2',
            'password' => 'password123',
            'email' => 'admin@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 搜索包含"test"的邮箱
        $result = $this->service->getUserList(1, 10, 'test');
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('test@example.com', $result->items()[0]->email);
    }
    
    /**
     * 测试搜索用户（按手机号）
     * 验证需求: 2.6
     */
    public function testSearchUsersByPhone(): void
    {
        // 创建用户
        $this->service->createUser([
            'username' => 'user1',
            'password' => 'password123',
            'email' => 'user1@example.com',
            'phone' => '13800138000',
            'role' => 'user',
            'status' => 1
        ]);
        
        $this->service->createUser([
            'username' => 'user2',
            'password' => 'password123',
            'email' => 'user2@example.com',
            'phone' => '13900139000',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 搜索包含"138"的手机号
        $result = $this->service->getUserList(1, 10, '138');
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('13800138000', $result->items()[0]->phone);
    }
    
    /**
     * 测试更新用户状态
     * 验证需求: 2.5
     */
    public function testUpdateUserStatus(): void
    {
        // 创建用户
        $user = $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 禁用用户
        $updatedUser = $this->service->updateStatus($user->id, 0);
        
        $this->assertEquals(0, $updatedUser->status);
        
        // 启用用户
        $updatedUser = $this->service->updateStatus($user->id, 1);
        
        $this->assertEquals(1, $updatedUser->status);
    }
    
    /**
     * 测试更新用户状态时状态值无效
     * 验证需求: 2.5
     */
    public function testUpdateUserStatusWithInvalidValue(): void
    {
        // 创建用户
        $user = $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus($user->id, 2);
    }
    
    /**
     * 测试根据ID获取用户
     */
    public function testGetUserById(): void
    {
        // 创建用户
        $user = $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 获取用户
        $foundUser = $this->service->getUserById($user->id);
        
        $this->assertNotNull($foundUser);
        $this->assertEquals('testuser', $foundUser->username);
    }
    
    /**
     * 测试根据用户名获取用户
     */
    public function testGetUserByUsername(): void
    {
        // 创建用户
        $this->service->createUser([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 获取用户
        $foundUser = $this->service->getUserByUsername('testuser');
        
        $this->assertNotNull($foundUser);
        $this->assertEquals('testuser', $foundUser->username);
    }
}
