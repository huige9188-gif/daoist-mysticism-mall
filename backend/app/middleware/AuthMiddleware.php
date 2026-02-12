<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use app\common\Jwt;

/**
 * JWT认证中间件
 * 验证JWT令牌，提取用户信息，检查权限
 * 
 * 验证需求: 11.1, 11.2, 11.4
 */
class AuthMiddleware
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 从请求头获取token
        $token = $this->getTokenFromRequest($request);
        
        // 如果没有token，返回401错误
        if (empty($token)) {
            return $this->unauthorizedResponse('未提供认证令牌');
        }
        
        // 验证token有效性
        $user = Jwt::getUserFromToken($token);
        
        if ($user === null) {
            return $this->unauthorizedResponse('认证令牌无效或已过期');
        }
        
        // 将用户信息注入请求，供后续使用
        $request->user = $user;
        
        return $next($request);
    }
    
    /**
     * 从请求中提取token
     * 支持两种方式：
     * 1. Authorization: Bearer <token>
     * 2. Authorization: <token>
     *
     * @param Request $request
     * @return string|null
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        $authorization = $request->header('Authorization', '');
        
        if (empty($authorization)) {
            return null;
        }
        
        // 处理 "Bearer <token>" 格式
        if (stripos($authorization, 'Bearer ') === 0) {
            return substr($authorization, 7);
        }
        
        // 直接返回token
        return $authorization;
    }
    
    /**
     * 返回未授权响应
     *
     * @param string $message 错误消息
     * @return Response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return json([
            'code' => 401,
            'message' => $message,
            'data' => null
        ], 401);
    }
}
