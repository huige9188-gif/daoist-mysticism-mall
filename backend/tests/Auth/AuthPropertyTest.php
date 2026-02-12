<?php

namespace Tests\Auth;

use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use app\model\User;
use app\common\Jwt;
use PDO;

/**
 * 认证属性测试
 * 
 * 使用Eris库进行基于属性的测试
 * 验证需求: 11.2
 */
class AuthPropertyTest extends TestCase
{
    use TestTrait;
    
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
     * Feature: daoist-mysticism-mall, Property 41: 正确凭证生成令牌
     * 
     * **Validates: Requirements 11.2**
     * 
     * 对于任意有效的用户名和正确的密码，登录操作应该返回有效的JWT令牌
     * 
     * 此属性测试验证：
     * 1. 使用正确的用户名和密码登录总是成功
     * 2. 返回的响应包含有效的JWT令牌
     * 3. 令牌可以被成功验证
     * 4. 从令牌中提取的用户信息与原始用户信息一致
     */
    public function testCorrectCredentialsGenerateToken(): void
    {
        $this->forAll(
            $this->generateUsername(),
            $this->generatePassword(),
            $this->generateEmail(),
            $this->generateRole()
        )->then(function ($username, $password, $email, $role) {
            // 创建测试用户
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = self::$pdo->prepare("
                INSERT INTO users (username, password, email, role, status) 
                VALUES (:username, :password, :email, :role, 1)
            ");
            $stmt->execute([
                'username' => $username,
                'password' => $hashedPassword,
                'email' => $email,
                'role' => $role
            ]);
            $userId = self::$pdo->lastInsertId();
            
            // 模拟登录
            $response = $this->simulateLogin($username, $password);
            
            // 验证登录成功
            $this->assertEquals(200, $response['code'], 
                "正确凭证应该返回200状态码");
            $this->assertEquals('登录成功', $response['message']);
            
            // 验证返回了token
            $this->assertArrayHasKey('token', $response['data'], 
                "响应应该包含token字段");
            $this->assertNotEmpty($response['data']['token'], 
                "token不应该为空");
            
            // 验证token是有效的JWT格式（三个部分）
            $token = $response['data']['token'];
            $parts = explode('.', $token);
            $this->assertCount(3, $parts, 
                "JWT令牌应该包含三个部分（header.payload.signature）");
            
            // 验证token可以被成功验证
            $decoded = Jwt::verifyToken($token);
            $this->assertNotNull($decoded, 
                "生成的令牌应该可以被成功验证");
            
            // 验证token包含正确的用户信息
            $this->assertEquals($userId, $decoded->id, 
                "令牌中的用户ID应该与创建的用户ID一致");
            $this->assertEquals($username, $decoded->username, 
                "令牌中的用户名应该与登录用户名一致");
            $this->assertEquals($email, $decoded->email, 
                "令牌中的邮箱应该与用户邮箱一致");
            $this->assertEquals($role, $decoded->role, 
                "令牌中的角色应该与用户角色一致");
            
            // 验证token包含标准JWT声明
            $this->assertObjectHasProperty('iat', $decoded, 
                "令牌应该包含签发时间（iat）");
            $this->assertObjectHasProperty('exp', $decoded, 
                "令牌应该包含过期时间（exp）");
            
            // 验证过期时间在未来
            $this->assertGreaterThan(time(), $decoded->exp, 
                "令牌过期时间应该在未来");
            
            // 验证响应包含用户信息
            $this->assertArrayHasKey('user', $response['data'], 
                "响应应该包含用户信息");
            $this->assertEquals($username, $response['data']['user']['username'], 
                "响应中的用户名应该正确");
            $this->assertEquals($email, $response['data']['user']['email'], 
                "响应中的邮箱应该正确");
            $this->assertEquals($role, $response['data']['user']['role'], 
                "响应中的角色应该正确");
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 42: 错误凭证拒绝登录
     * 
     * **Validates: Requirements 11.3**
     * 
     * 对于任意用户名和错误的密码，登录操作应该返回401错误
     * 
     * 此属性测试验证：
     * 1. 使用错误的密码登录总是失败
     * 2. 返回401状态码
     * 3. 不返回JWT令牌
     * 4. 返回适当的错误消息
     */
    public function testIncorrectCredentialsRejectLogin(): void
    {
        $this->forAll(
            $this->generateUsername(),
            $this->generatePassword(),
            $this->generatePassword(), // 生成不同的错误密码
            $this->generateEmail(),
            $this->generateRole()
        )->then(function ($username, $correctPassword, $wrongPassword, $email, $role) {
            // 确保错误密码与正确密码不同
            if ($correctPassword === $wrongPassword) {
                $wrongPassword = $wrongPassword . '_wrong';
            }
            
            // 创建测试用户
            $hashedPassword = password_hash($correctPassword, PASSWORD_DEFAULT);
            $stmt = self::$pdo->prepare("
                INSERT INTO users (username, password, email, role, status) 
                VALUES (:username, :password, :email, :role, 1)
            ");
            $stmt->execute([
                'username' => $username,
                'password' => $hashedPassword,
                'email' => $email,
                'role' => $role
            ]);
            
            // 使用错误密码尝试登录
            $response = $this->simulateLogin($username, $wrongPassword);
            
            // 验证登录失败
            $this->assertEquals(401, $response['code'], 
                "错误凭证应该返回401状态码");
            
            // 验证错误消息
            $this->assertStringContainsString('错误', $response['message'], 
                "错误消息应该包含'错误'字样");
            
            // 验证没有返回token
            $this->assertNull($response['data'], 
                "错误凭证不应该返回任何数据");
        });
    }
    
    /**
     * 生成用户名生成器
     * 
     * 生成3-20个字符的字母数字用户名
     */
    private function generateUsername()
    {
        return \Eris\Generator\map(
            function ($str) {
                // 只保留字母数字字符
                $str = preg_replace('/[^a-z0-9]/', '', strtolower($str));
                // 确保长度在3-20之间
                $str = substr($str, 0, 20);
                if (strlen($str) < 3) {
                    $str = str_pad($str, 3, 'a');
                }
                return $str;
            },
            \Eris\Generator\string()
        );
    }
    
    /**
     * 生成密码生成器
     * 
     * 生成6-50个字符的密码
     */
    private function generatePassword()
    {
        return \Eris\Generator\map(
            function ($str) {
                // 确保长度在6-50之间
                $str = substr($str, 0, 50);
                if (strlen($str) < 6) {
                    $str = str_pad($str, 6, 'x');
                }
                return $str;
            },
            \Eris\Generator\string()
        );
    }
    
    /**
     * 生成邮箱生成器
     * 
     * 生成有效的邮箱地址
     */
    private function generateEmail()
    {
        return \Eris\Generator\map(
            function ($str) {
                // 只保留字母数字字符
                $str = preg_replace('/[^a-z0-9]/', '', strtolower($str));
                // 确保有效的邮箱格式
                $str = substr($str, 0, 20);
                if (strlen($str) < 3) {
                    $str = str_pad($str, 3, 'a');
                }
                return $str . '@example.com';
            },
            \Eris\Generator\string()
        );
    }
    
    /**
     * 生成角色生成器
     * 
     * 生成'user'或'admin'角色
     */
    private function generateRole()
    {
        return \Eris\Generator\elements('user', 'admin');
    }
    
    /**
     * 模拟登录请求
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @return array 登录响应
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
        
        // 生成JWT令牌
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        $token = Jwt::generateToken($payload);
        
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
