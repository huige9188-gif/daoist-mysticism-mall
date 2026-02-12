<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use app\common\Jwt;

/**
 * JWT工具类测试
 * 
 * 验证需求: 11.2, 11.4
 * 
 * 测试目标:
 * - 验证JWT令牌生成功能
 * - 验证JWT令牌验证功能
 * - 验证过期令牌被拒绝
 * - 验证无效令牌被拒绝
 */
class JwtTest extends TestCase
{
    /**
     * 测试生成有效的JWT令牌
     * 
     * 验证需求: 11.2 - 登录凭证正确时生成JWT令牌
     */
    public function testGenerateValidToken(): void
    {
        $payload = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user'
        ];
        
        $token = Jwt::generateToken($payload);
        
        // 验证token不为空
        $this->assertNotEmpty($token);
        
        // 验证token是字符串
        $this->assertIsString($token);
        
        // 验证token包含三个部分（header.payload.signature）
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }
    
    /**
     * 测试验证有效的JWT令牌
     * 
     * 验证需求: 11.4 - 验证JWT令牌的有效性
     */
    public function testVerifyValidToken(): void
    {
        $payload = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'admin'
        ];
        
        $token = Jwt::generateToken($payload);
        $decoded = Jwt::verifyToken($token);
        
        // 验证解码成功
        $this->assertNotNull($decoded);
        
        // 验证载荷数据正确
        $this->assertEquals(1, $decoded->id);
        $this->assertEquals('testuser', $decoded->username);
        $this->assertEquals('test@example.com', $decoded->email);
        $this->assertEquals('admin', $decoded->role);
        
        // 验证包含标准声明
        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertObjectHasProperty('exp', $decoded);
    }
    
    /**
     * 测试从令牌中提取用户信息
     * 
     * 验证需求: 11.4 - 验证JWT令牌并提取用户信息
     */
    public function testGetUserFromToken(): void
    {
        $payload = [
            'id' => 2,
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ];
        
        $token = Jwt::generateToken($payload);
        $user = Jwt::getUserFromToken($token);
        
        // 验证用户信息提取成功
        $this->assertNotNull($user);
        $this->assertIsArray($user);
        
        // 验证用户信息正确
        $this->assertEquals(2, $user['id']);
        $this->assertEquals('adminuser', $user['username']);
        $this->assertEquals('admin@example.com', $user['email']);
        $this->assertEquals('admin', $user['role']);
    }
    
    /**
     * 测试验证无效的JWT令牌
     * 
     * 验证需求: 11.5 - 无效令牌返回401错误
     */
    public function testVerifyInvalidToken(): void
    {
        $invalidToken = 'invalid.token.here';
        
        $decoded = Jwt::verifyToken($invalidToken);
        
        // 验证返回null
        $this->assertNull($decoded);
    }
    
    /**
     * 测试验证格式错误的JWT令牌
     * 
     * 验证需求: 11.5 - 无效令牌返回401错误
     */
    public function testVerifyMalformedToken(): void
    {
        $malformedToken = 'not-a-valid-jwt';
        
        $decoded = Jwt::verifyToken($malformedToken);
        
        // 验证返回null
        $this->assertNull($decoded);
    }
    
    /**
     * 测试验证空令牌
     * 
     * 验证需求: 11.5 - 无效令牌返回401错误
     */
    public function testVerifyEmptyToken(): void
    {
        $emptyToken = '';
        
        $decoded = Jwt::verifyToken($emptyToken);
        
        // 验证返回null
        $this->assertNull($decoded);
    }
    
    /**
     * 测试从无效令牌中提取用户信息
     * 
     * 验证需求: 11.5 - 无效令牌返回401错误
     */
    public function testGetUserFromInvalidToken(): void
    {
        $invalidToken = 'invalid.token.here';
        
        $user = Jwt::getUserFromToken($invalidToken);
        
        // 验证返回null
        $this->assertNull($user);
    }
    
    /**
     * 测试令牌包含过期时间
     * 
     * 验证需求: 11.2 - 生成的JWT令牌应包含过期时间
     */
    public function testTokenContainsExpiration(): void
    {
        $payload = [
            'id' => 1,
            'username' => 'testuser'
        ];
        
        $token = Jwt::generateToken($payload);
        $decoded = Jwt::verifyToken($token);
        
        // 验证包含过期时间
        $this->assertObjectHasProperty('exp', $decoded);
        
        // 验证过期时间在未来
        $this->assertGreaterThan(time(), $decoded->exp);
        
        // 验证过期时间大约是当前时间 + 配置的过期时间（默认7200秒）
        $expectedExpire = time() + 7200;
        $this->assertEqualsWithDelta($expectedExpire, $decoded->exp, 5);
    }
    
    /**
     * 测试令牌包含签发时间
     * 
     * 验证需求: 11.2 - 生成的JWT令牌应包含签发时间
     */
    public function testTokenContainsIssuedAt(): void
    {
        $payload = [
            'id' => 1,
            'username' => 'testuser'
        ];
        
        $token = Jwt::generateToken($payload);
        $decoded = Jwt::verifyToken($token);
        
        // 验证包含签发时间
        $this->assertObjectHasProperty('iat', $decoded);
        
        // 验证签发时间接近当前时间
        $this->assertEqualsWithDelta(time(), $decoded->iat, 5);
    }
}
