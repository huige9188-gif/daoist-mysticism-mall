<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 管理员权限中间件
 * 验证用户是否具有管理员角色
 * 
 * 验证需求: 11.6, 11.7
 * 
 * 注意：此中间件必须在AuthMiddleware之后使用
 */
class AdminMiddleware
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
        // 获取用户信息（由AuthMiddleware注入）
        $user = $request->user ?? null;
        
        // 如果没有用户信息，返回401错误
        if ($user === null) {
            return $this->unauthorizedResponse('未授权访问');
        }
        
        // 检查用户角色是否为admin
        $role = $user['role'] ?? 'user';
        
        if ($role !== 'admin') {
            return $this->forbiddenResponse('无权限访问，需要管理员权限');
        }
        
        return $next($request);
    }
    
    /**
     * 返回未授权响应
     *
     * @param string $message 错误消息
     * @return Response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return Response::create([
            'code' => 401,
            'message' => $message,
            'data' => null
        ], 'json', 401);
    }
    
    /**
     * 返回禁止访问响应
     *
     * @param string $message 错误消息
     * @return Response
     */
    private function forbiddenResponse(string $message): Response
    {
        return Response::create([
            'code' => 403,
            'message' => $message,
            'data' => null
        ], 'json', 403);
    }
}
