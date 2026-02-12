<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use app\middleware\AuthMiddleware;
use app\common\Jwt;
use think\Request;
use think\Response;

/**
 * 认证中间件测试
 * 
 * 验证需求: 11.1, 11.4, 11.5
 * 
 * 测试目标:
 * - 验证有效令牌允许访问
 * - 验证无效令牌拒绝访问
 * - 验证缺少令牌拒绝访问
 * - 验证用户信息正确注入请求
 */
class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;
    
    protected function setUp(): void
    {
        $this->middleware = new AuthMiddleware();
    }
    
    /**
     * 测试有效令牌允许访问
     * 
     * 验证需求: 11.4 - 有效JWT令牌允许访问受保护的API端点
     */
    public function testValidTokenAllowsAccess(): void
    {
        // 生成有效令牌
        $payload = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user'
        ];
        $token = Jwt::generateToken($payload);
        
        // 创建模拟请求
        $request = $this->createMockRequest('Bearer ' . $token);
        
        // 创建next闭包
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return json(['success' => true]);
        };
        
        // 执行中间件
        $response = $this->middleware->handle($request, $next);
        
        // 验证next被调用
        $this->assertTrue($nextCalled, '中间件应该调用next闭包');
        
        // 验证用户信息被注入到请求中
        $this->assertNotNull($request->user);
        $this->assertEquals(1, $request->user['id']);
        $this->assertEquals('testuser', $request->user['username']);
    }
    
    /**
     * 测试Bearer格式的令牌
     * 
     * 验证需求: 11.4 - 支持Bearer格式的Authorization头
     */
    public function testBearerTokenFormat(): void
    {
        $payload = ['id' => 1, 'username' => 'testuser'];
        $token = Jwt::generateToken($payload);
        
        $request = $this->createMockRequest('Bearer ' . $token);
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return json(['success' => true]);
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertTrue($nextCalled);
        $this->assertNotNull($request->user);
    }
    
    /**
     * 测试直接令牌格式（不带Bearer前缀）
     * 
     * 验证需求: 11.4 - 支持直接令牌格式的Authorization头
     */
    public function testDirectTokenFormat(): void
    {
        $payload = ['id' => 1, 'username' => 'testuser'];
        $token = Jwt::generateToken($payload);
        
        $request = $this->createMockRequest($token);
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return json(['success' => true]);
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertTrue($nextCalled);
        $this->assertNotNull($request->user);
    }
    
    /**
     * 测试缺少令牌返回401错误
     * 
     * 验证需求: 11.5 - 缺少令牌返回401错误
     */
    public function testMissingTokenReturns401(): void
    {
        $request = $this->createMockRequest('');
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return json(['success' => true]);
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回401状态码
        $this->assertEquals(401, $response->getCode());
        
        // 验证响应内容
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $data['code']);
        $this->assertStringContainsString('未提供认证令牌', $data['message']);
    }
    
    /**
     * 测试无效令牌返回401错误
     * 
     * 验证需求: 11.5 - 无效令牌返回401错误
     */
    public function testInvalidTokenReturns401(): void
    {
        $request = $this->createMockRequest('Bearer invalid.token.here');
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return json(['success' => true]);
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回401状态码
        $this->assertEquals(401, $response->getCode());
        
        // 验证响应内容
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $data['code']);
        $this->assertStringContainsString('认证令牌无效或已过期', $data['message']);
    }
    
    /**
     * 测试格式错误的令牌返回401错误
     * 
     * 验证需求: 11.5 - 无效令牌返回401错误
     */
    public function testMalformedTokenReturns401(): void
    {
        $request = $this->createMockRequest('Bearer not-a-valid-jwt');
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return json(['success' => true]);
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回401状态码
        $this->assertEquals(401, $response->getCode());
        
        // 验证响应内容
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $data['code']);
    }
    
    /**
     * 测试用户信息正确注入到请求中
     * 
     * 验证需求: 11.4 - 验证JWT令牌并提取用户信息
     */
    public function testUserInfoInjectedIntoRequest(): void
    {
        $payload = [
            'id' => 5,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'role' => 'admin'
        ];
        $token = Jwt::generateToken($payload);
        
        $request = $this->createMockRequest('Bearer ' . $token);
        
        $next = function ($req) {
            return json(['success' => true]);
        };
        
        $this->middleware->handle($request, $next);
        
        // 验证用户信息完整注入
        $this->assertNotNull($request->user);
        $this->assertEquals(5, $request->user['id']);
        $this->assertEquals('john_doe', $request->user['username']);
        $this->assertEquals('john@example.com', $request->user['email']);
        $this->assertEquals('admin', $request->user['role']);
    }
    
    /**
     * 创建模拟请求对象
     */
    private function createMockRequest(string $authorizationHeader): Request
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['header'])
            ->addMethods([]) // Allow dynamic properties
            ->getMock();
        
        $request->expects($this->any())
            ->method('header')
            ->willReturnCallback(function ($name, $default = '') use ($authorizationHeader) {
                if ($name === 'Authorization') {
                    return $authorizationHeader ?: $default;
                }
                return $default;
            });
        
        return $request;
    }
}
