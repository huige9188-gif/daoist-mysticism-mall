<?php

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use app\common\Jwt;
use think\Request;

/**
 * 认证控制器
 * 
 * 验证需求: 11.1, 11.2, 11.3
 */
class Auth extends BaseController
{
    /**
     * 用户登录
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function login(Request $request)
    {
        // 获取登录参数
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        
        // 验证必填字段
        if (empty($username) || empty($password)) {
            return json([
                'code' => 400,
                'message' => '用户名和密码不能为空',
                'data' => null
            ], 400);
        }
        
        // 查找用户
        $user = User::where('username', $username)
            ->whereNull('deleted_at')
            ->find();
        
        // 用户不存在
        if (!$user) {
            return json([
                'code' => 401,
                'message' => '用户名或密码错误',
                'data' => null
            ], 401);
        }
        
        // 验证密码
        if (!password_verify($password, $user->password)) {
            return json([
                'code' => 401,
                'message' => '用户名或密码错误',
                'data' => null
            ], 401);
        }
        
        // 检查用户状态
        if ($user->status != 1) {
            return json([
                'code' => 403,
                'message' => '账号已被禁用',
                'data' => null
            ], 403);
        }
        
        // 生成JWT令牌
        $payload = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role
        ];
        
        $token = Jwt::generateToken($payload);
        
        // 返回成功响应
        return json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]
        ]);
    }
    
    /**
     * 用户登出
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function logout(Request $request)
    {
        // JWT是无状态的，登出只需要客户端删除token
        // 这里可以实现token黑名单机制（可选）
        
        return json([
            'code' => 200,
            'message' => '登出成功',
            'data' => null
        ]);
    }
    
    /**
     * 获取当前用户信息
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function me(Request $request)
    {
        $user = $request->user;
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $user
        ]);
    }
}
