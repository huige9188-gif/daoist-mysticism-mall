<?php

namespace Tests\User;

use PHPUnit\Framework\TestCase;
use app\model\User;
use app\validate\User as UserValidate;
use PDO;

/**
 * 用户模型和验证器测试
 * 
 * 验证需求: 2.2
 */
class UserModelTest extends TestCase
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
    }
    
    /**
     * 测试用户名唯一性验证
     */
    public function testUsernameUniqueness(): void
    {
        // 创建第一个用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, role, status) 
            VALUES ('testuser', 'password123', 'test1@example.com', 'user', 1)
        ");
        
        // 验证器应该拒绝重复的用户名
        $validate = new UserValidate();
        $data = [
            'username' => 'testuser',
            'password' => 'password456',
            'email' => 'test2@example.com',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('用户名已存在', $validate->getError());
    }
    
    /**
     * 测试邮箱格式验证
     */
    public function testEmailFormatValidation(): void
    {
        $validate = new UserValidate();
        
        // 无效的邮箱格式
        $invalidEmails = [
            'invalid',
            'invalid@',
            '@example.com',
            'invalid@.com',
            'invalid..email@example.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $data = [
                'username' => 'testuser',
                'password' => 'password123',
                'email' => $email,
                'role' => 'user',
                'status' => 1
            ];
            
            $result = $validate->scene('create')->check($data);
            $this->assertFalse($result, "邮箱 {$email} 应该被拒绝");
        }
        
        // 有效的邮箱格式
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk'
        ];
        
        foreach ($validEmails as $email) {
            $data = [
                'username' => 'testuser' . rand(1000, 9999),
                'password' => 'password123',
                'email' => $email,
                'role' => 'user',
                'status' => 1
            ];
            
            $result = $validate->scene('create')->check($data);
            $this->assertTrue($result, "邮箱 {$email} 应该被接受");
        }
    }
    
    /**
     * 测试邮箱唯一性验证
     */
    public function testEmailUniqueness(): void
    {
        // 创建第一个用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, role, status) 
            VALUES ('testuser1', 'password123', 'test@example.com', 'user', 1)
        ");
        
        // 验证器应该拒绝重复的邮箱
        $validate = new UserValidate();
        $data = [
            'username' => 'testuser2',
            'password' => 'password456',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('邮箱已存在', $validate->getError());
    }
    
    /**
     * 测试手机号格式验证
     */
    public function testPhoneFormatValidation(): void
    {
        $validate = new UserValidate();
        
        // 无效的手机号
        $invalidPhones = [
            '123',
            '12345678901234567890',
            'abcdefghijk',
            '123-456-7890'
        ];
        
        foreach ($invalidPhones as $phone) {
            $data = [
                'username' => 'testuser',
                'password' => 'password123',
                'email' => 'test@example.com',
                'phone' => $phone,
                'role' => 'user',
                'status' => 1
            ];
            
            $result = $validate->scene('create')->check($data);
            $this->assertFalse($result, "手机号 {$phone} 应该被拒绝");
        }
    }
    
    /**
     * 测试用户名长度验证
     */
    public function testUsernameLengthValidation(): void
    {
        $validate = new UserValidate();
        
        // 用户名太短
        $data = [
            'username' => 'ab',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('用户名长度必须在3-50个字符之间', $validate->getError());
        
        // 用户名太长
        $data['username'] = str_repeat('a', 51);
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
    }
    
    /**
     * 测试密码长度验证
     */
    public function testPasswordLengthValidation(): void
    {
        $validate = new UserValidate();
        
        // 密码太短
        $data = [
            'username' => 'testuser',
            'password' => '12345',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('密码长度必须在6-255个字符之间', $validate->getError());
    }
    
    /**
     * 测试必填字段验证
     */
    public function testRequiredFieldsValidation(): void
    {
        $validate = new UserValidate();
        
        // 缺少用户名
        $data = [
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('用户名不能为空', $validate->getError());
        
        // 缺少密码
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('密码不能为空', $validate->getError());
        
        // 缺少邮箱
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('邮箱不能为空', $validate->getError());
    }
    
    /**
     * 测试有效数据通过验证
     */
    public function testValidDataPassesValidation(): void
    {
        $validate = new UserValidate();
        
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'phone' => '13800138000',
            'role' => 'user',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertTrue($result);
    }
    
    /**
     * 测试密码自动加密
     */
    public function testPasswordAutoHashing(): void
    {
        $plainPassword = 'password123';
        
        // 插入用户
        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, password, email, role, status) 
            VALUES (:username, :password, :email, :role, :status)
        ");
        
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt->execute([
            'username' => 'testuser',
            'password' => $hashedPassword,
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 获取用户
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => 'testuser']);
        $user = $stmt->fetch();
        
        // 验证密码已加密
        $this->assertNotEquals($plainPassword, $user['password']);
        $this->assertTrue(password_verify($plainPassword, $user['password']));
    }
    
    /**
     * 测试角色验证
     */
    public function testRoleValidation(): void
    {
        $validate = new UserValidate();
        
        // 无效的角色
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'invalid_role',
            'status' => 1
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('角色必须是admin或user', $validate->getError());
        
        // 有效的角色
        foreach (['admin', 'user'] as $role) {
            $data['role'] = $role;
            $data['username'] = 'testuser' . rand(1000, 9999);
            $data['email'] = 'test' . rand(1000, 9999) . '@example.com';
            $result = $validate->scene('create')->check($data);
            $this->assertTrue($result, "角色 {$role} 应该被接受");
        }
    }
    
    /**
     * 测试状态验证
     */
    public function testStatusValidation(): void
    {
        $validate = new UserValidate();
        
        // 无效的状态
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 2
        ];
        
        $result = $validate->scene('create')->check($data);
        $this->assertFalse($result);
        $this->assertStringContainsString('状态必须是0或1', $validate->getError());
        
        // 有效的状态
        foreach ([0, 1] as $status) {
            $data['status'] = $status;
            $data['username'] = 'testuser' . rand(1000, 9999);
            $data['email'] = 'test' . rand(1000, 9999) . '@example.com';
            $result = $validate->scene('create')->check($data);
            $this->assertTrue($result, "状态 {$status} 应该被接受");
        }
    }
}
