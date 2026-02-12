<?php

namespace app\controller\api;

use app\BaseController;
use app\service\UserService;
use think\Request;
use think\exception\ValidateException;

/**
 * 用户管理控制器
 * 
 * 验证需求: 2.1, 2.2, 2.3, 2.4, 2.5
 */
class User extends BaseController
{
    protected $userService;
    
    public function __construct()
    {
        $this->userService = new UserService();
    }
    
    /**
     * 获取用户列表
     * GET /api/users
     * 
     * 验证需求: 2.1, 2.6
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        $search = $request->get('search', '');
        
        $users = $this->userService->getUserList($page, $pageSize, $search);
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $users
        ]);
    }
    
    /**
     * 创建用户
     * POST /api/users
     * 
     * 验证需求: 2.2
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            // 如果提供了密码，进行加密
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $user = $this->userService->createUser($data);
            
            // 移除密码字段
            unset($user->password);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $user
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    /**
     * 更新用户
     * PUT /api/users/:id
     * 
     * 验证需求: 2.3
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->put();
            
            $user = $this->userService->updateUser($id, $data);
            
            // 移除密码字段
            unset($user->password);
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $user
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
    
    /**
     * 删除用户
     * DELETE /api/users/:id
     * 
     * 验证需求: 2.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $this->userService->deleteUser($id);
            
            return json([
                'code' => 200,
                'message' => '删除成功',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
    
    /**
     * 更新用户状态
     * PATCH /api/users/:id/status
     * 
     * 验证需求: 2.5
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $status = $request->patch('status');
            
            if ($status === null) {
                return json([
                    'code' => 400,
                    'message' => '状态参数不能为空',
                    'data' => null
                ], 400);
            }
            
            $user = $this->userService->updateStatus($id, $status);
            
            // 移除密码字段
            unset($user->password);
            
            return json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => $user
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
}
