<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use app\middleware\AdminMiddleware;
use think\Request;
use think\Response;

/**
 * 管理员权限中间件测试
 * 
 * 验证需求: 11.6, 11.7
 * 
 * 测试目标:
 * - 验证Admin角色允许访问管理功能
 * - 验证普通用户拒绝访问管理功能
 * - 验证未认证用户拒绝访问
 */
class AdminMiddlewareTest extends TestCase
{
    private AdminMiddleware $middleware;
    
    protected function setUp(): void
    {
        $this->middleware = new AdminMiddleware();
    }
    
    /**
     * 测试Admin角色允许访问
     * 
     * 验证需求: 11.6 - Admin角色可以访问管理功能
     */
    public function testAdminRoleAllowsAccess(): void
    {
        $user = [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ];
        
        $request = $this->createMockRequestWithUser($user);
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return Response::create(['success' => true], 'json');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证next被调用
        $this->assertTrue($nextCalled, '管理员应该被允许访问');
    }
    
    /**
     * 测试普通用户返回403错误
     * 
     * 验证需求: 11.7 - 普通用户访问管理功能返回403错误
     */
    public function testUserRoleReturns403(): void
    {
        $user = [
            'id' => 2,
            'username' => 'normaluser',
            'email' => 'user@example.com',
            'role' => 'user'
        ];
        
        $request = $this->createMockRequestWithUser($user);
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return Response::create(['success' => true], 'json');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回403状态码
        $this->assertEquals(403, $response->getCode());
        
        // 验证响应内容
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(403, $data['code']);
        $this->assertStringContainsString('无权限访问', $data['message']);
        $this->assertStringContainsString('管理员权限', $data['message']);
    }
    
    /**
     * 测试未认证用户返回401错误
     * 
     * 验证需求: 11.7 - 未认证用户拒绝访问
     */
    public function testUnauthenticatedUserReturns401(): void
    {
        $request = $this->createMockRequestWithUser(null);
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return Response::create(['success' => true], 'json');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回401状态码
        $this->assertEquals(401, $response->getCode());
        
        // 验证响应内容
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $data['code']);
        $this->assertStringContainsString('未授权访问', $data['message']);
    }
    
    /**
     * 测试缺少role字段的用户被视为普通用户
     * 
     * 验证需求: 11.7 - 默认角色为user
     */
    public function testMissingRoleDefaultsToUser(): void
    {
        $user = [
            'id' => 3,
            'username' => 'noroluser',
            'email' => 'norole@example.com'
            // 没有role字段
        ];
        
        $request = $this->createMockRequestWithUser($user);
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return Response::create(['success' => true], 'json');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回403状态码（因为默认是user角色）
        $this->assertEquals(403, $response->getCode());
    }
    
    /**
     * 测试其他角色值被拒绝
     * 
     * 验证需求: 11.7 - 只有admin角色可以访问
     */
    public function testOtherRolesAreDenied(): void
    {
        $user = [
            'id' => 4,
            'username' => 'moderator',
            'email' => 'mod@example.com',
            'role' => 'moderator'
        ];
        
        $request = $this->createMockRequestWithUser($user);
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return Response::create(['success' => true], 'json');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回403状态码
        $this->assertEquals(403, $response->getCode());
    }
    
    /**
     * 测试大小写敏感的角色检查
     * 
     * 验证需求: 11.6 - 角色检查应该精确匹配
     */
    public function testRoleCheckIsCaseSensitive(): void
    {
        $user = [
            'id' => 5,
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'role' => 'Admin' // 大写A
        ];
        
        $request = $this->createMockRequestWithUser($user);
        
        $next = function ($req) {
            $this->fail('不应该调用next闭包');
            return Response::create(['success' => true], 'json');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // 验证返回403状态码（因为不是精确的'admin'）
        $this->assertEquals(403, $response->getCode());
    }
    
    /**
     * 创建带有用户信息的模拟请求对象
     */
    private function createMockRequestWithUser(?array $user): Request
    {
        $request = new class extends Request {
            public $user;
            
            public function __construct() {
                // 不调用父类构造函数以避免依赖
            }
        };
        
        $request->user = $user;
        
        return $request;
    }
}
