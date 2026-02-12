<?php

namespace Tests\Auth;

use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use app\middleware\AuthMiddleware;
use app\common\Jwt;
use think\Request;
use PDO;

/**
 * 授权属性测试
 * 
 * 使用Eris库进行基于属性的测试
 * 验证需求: 11.4, 11.5
 */
class AuthorizationPropertyTest extends TestCase
{
    use TestTrait;
    
    private static ?PDO $pdo = null;
    private AuthMiddleware $middleware;
    
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
        $this->middleware = new AuthMiddleware();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空用户表
        self::$pdo->exec("TRUNCATE TABLE users");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 43: 有效令牌访问授权
     * 
     * **Validates: Requirements 11.4**
     * 
     * 对于任意有效的JWT令牌，访问受保护的API端点应该被允许
     * 
     * 此属性测试验证：
     * 1. 使用有效的JWT令牌访问受保护的端点总是成功
     * 2. 中间件允许请求继续处理（调用next闭包）
     * 3. 用户信息被正确注入到请求对象中
     * 4. 注入的用户信息与令牌中的信息一致
     * 5. 对于任意用户数据（不同的ID、用户名、邮箱、角色），授权都能正确工作
     */
    public function testValidTokenGrantsAccess(): void
    {
        $this->forAll(
            $this->generateUserId(),
            $this->generateUsername(),
            $this->generateEmail(),
            $this->generateRole()
        )->then(function ($userId, $username, $email, $role) {
            // 创建测试用户
            $stmt = self::$pdo->prepare("
                INSERT INTO users (id, username, password, email, role, status) 
                VALUES (:id, :username, :password, :email, :role, 1)
            ");
            $stmt->execute([
                'id' => $userId,
                'username' => $username,
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'email' => $email,
                'role' => $role
            ]);
            
            // 生成有效的JWT令牌
            $payload = [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'role' => $role
            ];
            $token = Jwt::generateToken($payload);
            
            // 验证令牌是有效的
            $this->assertNotEmpty($token, "应该生成有效的令牌");
            
            // 创建带有有效令牌的请求
            $request = $this->createMockRequest('Bearer ' . $token);
            
            // 创建next闭包来验证中间件是否允许请求继续
            $nextCalled = false;
            $capturedRequest = null;
            $next = function ($req) use (&$nextCalled, &$capturedRequest) {
                $nextCalled = true;
                $capturedRequest = $req;
                return json(['success' => true, 'message' => '访问成功']);
            };
            
            // 执行中间件
            $response = $this->middleware->handle($request, $next);
            
            // 验证中间件允许请求继续（调用了next闭包）
            $this->assertTrue($nextCalled, 
                "有效令牌应该允许请求继续处理");
            
            // 验证响应是成功的（来自next闭包）
            $this->assertNotNull($response, 
                "应该返回响应");
            $responseData = json_decode($response->getContent(), true);
            $this->assertTrue($responseData['success'], 
                "响应应该表示成功");
            
            // 验证用户信息被注入到请求中
            $this->assertNotNull($capturedRequest->user, 
                "用户信息应该被注入到请求对象中");
            
            // 验证注入的用户信息与令牌中的信息一致
            $this->assertEquals($userId, $capturedRequest->user['id'], 
                "注入的用户ID应该与令牌中的ID一致");
            $this->assertEquals($username, $capturedRequest->user['username'], 
                "注入的用户名应该与令牌中的用户名一致");
            $this->assertEquals($email, $capturedRequest->user['email'], 
                "注入的邮箱应该与令牌中的邮箱一致");
            $this->assertEquals($role, $capturedRequest->user['role'], 
                "注入的角色应该与令牌中的角色一致");
            
            // 验证令牌包含标准JWT声明
            $this->assertArrayHasKey('iat', $capturedRequest->user, 
                "用户信息应该包含签发时间");
            $this->assertArrayHasKey('exp', $capturedRequest->user, 
                "用户信息应该包含过期时间");
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 44: 无效令牌拒绝访问
     * 
     * **Validates: Requirements 11.5**
     * 
     * 对于任意无效或过期的JWT令牌，访问受保护的API端点应该返回401错误
     * 
     * 此属性测试验证：
     * 1. 使用无效令牌访问受保护的端点总是被拒绝
     * 2. 中间件不允许请求继续处理（不调用next闭包）
     * 3. 返回401状态码
     * 4. 返回适当的错误消息
     * 5. 不注入用户信息到请求对象中
     * 6. 对于各种类型的无效令牌（格式错误、签名错误、过期等），都能正确拒绝
     */
    public function testInvalidTokenDeniesAccess(): void
    {
        $this->forAll(
            $this->generateInvalidToken()
        )->then(function ($invalidToken) {
            // 创建带有无效令牌的请求
            $request = $this->createMockRequest('Bearer ' . $invalidToken);
            
            // 创建next闭包来验证中间件是否拒绝请求
            $nextCalled = false;
            $next = function ($req) use (&$nextCalled) {
                $nextCalled = true;
                $this->fail('中间件不应该调用next闭包，因为令牌无效');
                return json(['success' => true]);
            };
            
            // 执行中间件
            $response = $this->middleware->handle($request, $next);
            
            // 验证中间件拒绝请求（没有调用next闭包）
            $this->assertFalse($nextCalled, 
                "无效令牌不应该允许请求继续处理");
            
            // 验证返回401状态码
            $this->assertEquals(401, $response->getCode(), 
                "无效令牌应该返回401状态码");
            
            // 验证响应内容
            $responseData = json_decode($response->getContent(), true);
            $this->assertNotNull($responseData, 
                "响应应该包含JSON数据");
            $this->assertEquals(401, $responseData['code'], 
                "响应code字段应该是401");
            $this->assertNotEmpty($responseData['message'], 
                "响应应该包含错误消息");
            $this->assertStringContainsString('令牌', $responseData['message'], 
                "错误消息应该提到令牌");
            $this->assertNull($responseData['data'], 
                "响应data字段应该为null");
            
            // 验证用户信息没有被注入到请求中
            $this->assertObjectNotHasProperty('user', $request, 
                "无效令牌不应该注入用户信息到请求对象中");
        });
    }
    
    /**
     * 测试缺少令牌拒绝访问
     * 
     * **Validates: Requirements 11.5**
     * 
     * 对于缺少Authorization头的请求，访问受保护的API端点应该返回401错误
     */
    public function testMissingTokenDeniesAccess(): void
    {
        $this->forAll(
            \Eris\Generator\elements('', null)
        )->then(function ($emptyToken) {
            // 创建没有令牌的请求
            $request = $this->createMockRequest($emptyToken ?? '');
            
            // 创建next闭包
            $nextCalled = false;
            $next = function ($req) use (&$nextCalled) {
                $nextCalled = true;
                $this->fail('中间件不应该调用next闭包，因为缺少令牌');
                return json(['success' => true]);
            };
            
            // 执行中间件
            $response = $this->middleware->handle($request, $next);
            
            // 验证中间件拒绝请求
            $this->assertFalse($nextCalled, 
                "缺少令牌不应该允许请求继续处理");
            
            // 验证返回401状态码
            $this->assertEquals(401, $response->getCode(), 
                "缺少令牌应该返回401状态码");
            
            // 验证响应内容
            $responseData = json_decode($response->getContent(), true);
            $this->assertEquals(401, $responseData['code']);
            $this->assertStringContainsString('未提供', $responseData['message'], 
                "错误消息应该提到未提供令牌");
        });
    }
    
    /**
     * 测试过期令牌拒绝访问
     * 
     * **Validates: Requirements 11.5**
     * 
     * 对于已过期的JWT令牌，访问受保护的API端点应该返回401错误
     */
    public function testExpiredTokenDeniesAccess(): void
    {
        $this->forAll(
            $this->generateUserId(),
            $this->generateUsername(),
            $this->generateEmail(),
            $this->generateRole()
        )->then(function ($userId, $username, $email, $role) {
            // 生成一个已过期的令牌（过期时间设置为过去）
            $expiredToken = $this->generateExpiredToken([
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'role' => $role
            ]);
            
            // 创建带有过期令牌的请求
            $request = $this->createMockRequest('Bearer ' . $expiredToken);
            
            // 创建next闭包
            $nextCalled = false;
            $next = function ($req) use (&$nextCalled) {
                $nextCalled = true;
                $this->fail('中间件不应该调用next闭包，因为令牌已过期');
                return json(['success' => true]);
            };
            
            // 执行中间件
            $response = $this->middleware->handle($request, $next);
            
            // 验证中间件拒绝请求
            $this->assertFalse($nextCalled, 
                "过期令牌不应该允许请求继续处理");
            
            // 验证返回401状态码
            $this->assertEquals(401, $response->getCode(), 
                "过期令牌应该返回401状态码");
            
            // 验证响应内容
            $responseData = json_decode($response->getContent(), true);
            $this->assertEquals(401, $responseData['code']);
            $this->assertStringContainsString('令牌', $responseData['message']);
        });
    }
    
    /**
     * 生成用户ID生成器
     * 
     * 生成1-1000000之间的正整数用户ID
     */
    private function generateUserId()
    {
        return \Eris\Generator\choose(1, 1000000);
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
     * 生成无效令牌生成器
     * 
     * 生成各种类型的无效令牌：
     * - 随机字符串
     * - 格式错误的JWT（少于3个部分）
     * - 签名错误的JWT
     * - 空字符串
     */
    private function generateInvalidToken()
    {
        return \Eris\Generator\oneOf(
            // 随机字符串
            \Eris\Generator\string(),
            // 格式错误的JWT（只有两个部分）
            \Eris\Generator\map(
                function ($str) {
                    return base64_encode($str) . '.' . base64_encode($str);
                },
                \Eris\Generator\string()
            ),
            // 格式错误的JWT（只有一个部分）
            \Eris\Generator\map(
                function ($str) {
                    return base64_encode($str);
                },
                \Eris\Generator\string()
            ),
            // 签名错误的JWT（使用错误的密钥签名）
            \Eris\Generator\map(
                function ($data) {
                    list($userId, $username) = $data;
                    return $this->generateTokenWithWrongSecret([
                        'id' => $userId,
                        'username' => $username
                    ]);
                },
                \Eris\Generator\tuple(
                    $this->generateUserId(),
                    $this->generateUsername()
                )
            ),
            // 简单的无效字符串
            \Eris\Generator\elements(
                'invalid',
                'not-a-token',
                '123456',
                'Bearer',
                'null',
                'undefined'
            )
        );
    }
    
    /**
     * 生成使用错误密钥的JWT令牌
     * 
     * @param array $payload 载荷数据
     * @return string JWT令牌
     */
    private function generateTokenWithWrongSecret(array $payload): string
    {
        $wrongSecret = 'wrong-secret-key-for-testing';
        $payload['iat'] = time();
        $payload['exp'] = time() + 7200;
        
        return \Firebase\JWT\JWT::encode($payload, $wrongSecret, 'HS256');
    }
    
    /**
     * 生成已过期的JWT令牌
     * 
     * @param array $payload 载荷数据
     * @return string 已过期的JWT令牌
     */
    private function generateExpiredToken(array $payload): string
    {
        $secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
        
        // 设置过期时间为过去（1小时前）
        $payload['iat'] = time() - 7200;
        $payload['exp'] = time() - 3600;
        
        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }
    
    /**
     * 创建模拟请求对象
     * 
     * @param string $authorizationHeader Authorization头的值
     * @return Request 模拟的请求对象
     */
    private function createMockRequest(string $authorizationHeader): Request
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['header'])
            ->getMock();
        
        $request->method('header')
            ->willReturnCallback(function ($name, $default = '') use ($authorizationHeader) {
                if ($name === 'Authorization') {
                    return $authorizationHeader ?: $default;
                }
                return $default;
            });
        
        return $request;
    }
}
