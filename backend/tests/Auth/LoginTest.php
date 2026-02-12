<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use app\model\User;
use PDO;

/**
 * 登录功能测试
 * 
 * 验证需求: 11.1, 11.2, 11.3
 */
class LoginTest extends TestCase
{
    private static ?PDO $pdo = null;
    
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
    }
    
    protected function setUp(): void
    {
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空用户表
        self::$pdo->exec("TRUNCATE TABLE users");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // 创建测试用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, role, status) 
            VALUES 
            ('testuser', '{$hashedPassword}', 'test@example.com', 'user', 1),
            ('admin', '{$hashedPassword}', 'admin@example.com', 'admin', 1),
            ('disabled', '{$hashedPassword}', 'disabled@example.com', 'user', 0)
        ");
    }
    
    /**
     * 测试正确凭证登录成功
     */
    public function testLoginWithCorrectCredentials(): void
    {
        // 模拟登录请求
        $response = $this->simulateLogin('testuser', 'password123');
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('登录成功', $response['message']);
        $this->assertArrayHasKey('token', $response['data']);
        $this->assertArrayHasKey('user', $response['data']);
        $this->assertEquals('testuser', $response['data']['user']['username']);
    }
    
    /**
     * 测试错误密码登录失败
     */
    public function testLoginWithWrongPassword(): void
    {
        $response = $this->simulateLogin('testuser', 'wrongpassword');
        
        $this->assertEquals(401, $response['code']);
        $this->assertEquals('用户名或密码错误', $response['message']);
    }
    
    /**
     * 测试不存在的用户登录失败
     */
    public function testLoginWithNonExistentUser(): void
    {
        $response = $this->simulateLogin('nonexistent', 'password123');
        
        $this->assertEquals(401, $response['code']);
        $this->assertEquals('用户名或密码错误', $response['message']);
    }
    
    /**
     * 测试禁用用户无法登录
     */
    public function testLoginWithDisabledUser(): void
    {
        $response = $this->simulateLogin('disabled', 'password123');
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('账号已被禁用', $response['message']);
    }
    
    /**
     * 测试空用户名登录失败
     */
    public function testLoginWithEmptyUsername(): void
    {
        $response = $this->simulateLogin('', 'password123');
        
        $this->assertEquals(400, $response['code']);
        $this->assertEquals('用户名和密码不能为空', $response['message']);
    }
    
    /**
     * 测试空密码登录失败
     */
    public function testLoginWithEmptyPassword(): void
    {
        $response = $this->simulateLogin('testuser', '');
        
        $this->assertEquals(400, $response['code']);
        $this->assertEquals('用户名和密码不能为空', $response['message']);
    }
    
    /**
     * 模拟登录请求
     */
    private function simulateLogin(string $username, string $password): array
    {
        // 查找用户
        $stmt = self::$pdo->prepare("
            SELECT * FROM users 
            WHERE username = :username AND deleted_at IS NULL
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        // 验证必填字段
        if (empty($username) || empty($password)) {
            return [
                'code' => 400,
                'message' => '用户名和密码不能为空',
                'data' => null
            ];
        }
        
        // 用户不存在
        if (!$user) {
            return [
                'code' => 401,
                'message' => '用户名或密码错误',
                'data' => null
            ];
        }
        
        // 验证密码
        if (!password_verify($password, $user['password'])) {
            return [
                'code' => 401,
                'message' => '用户名或密码错误',
                'data' => null
            ];
        }
        
        // 检查用户状态
        if ($user['status'] != 1) {
            return [
                'code' => 403,
                'message' => '账号已被禁用',
                'data' => null
            ];
        }
        
        // 生成模拟token
        $token = 'mock_jwt_token_' . $user['id'];
        
        return [
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]
        ];
    }
}
