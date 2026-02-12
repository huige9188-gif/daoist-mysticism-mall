<?php

namespace app\service;

use app\model\User;
use app\validate\User as UserValidate;
use think\exception\ValidateException;

/**
 * 用户服务类
 * 处理用户相关业务逻辑
 * 验证需求: 2.1, 2.2, 2.3, 2.4, 2.6
 */
class UserService
{
    /**
     * 创建用户
     * 验证需求: 2.2
     * 
     * @param array $data 用户数据
     * @return User
     * @throws ValidateException
     */
    public function createUser(array $data)
    {
        // 手动验证邮箱格式
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidateException('邮箱格式不正确');
        }
        
        // 手动验证用户名唯一性
        if (isset($data['username']) && User::where('username', $data['username'])->count() > 0) {
            throw new ValidateException('用户名已存在');
        }
        
        // 手动验证邮箱唯一性
        if (isset($data['email']) && User::where('email', $data['email'])->count() > 0) {
            throw new ValidateException('邮箱已存在');
        }
        
        // 验证数据（不包括unique规则和email规则）
        $validate = new UserValidate();
        if (!$validate->scene('create')->check($data)) {
            throw new ValidateException($validate->getError());
        }
        
        // 创建用户
        $user = User::create($data);
        
        return $user;
    }
    
    /**
     * 更新用户
     * 验证需求: 2.3
     * 
     * @param int $id 用户ID
     * @param array $data 更新数据
     * @return User
     * @throws \Exception
     */
    public function updateUser(int $id, array $data)
    {
        $user = User::find($id);
        if (!$user) {
            throw new \Exception('用户不存在');
        }
        
        // 验证数据（排除当前用户的唯一性检查）
        $validate = new UserValidate();
        
        // 如果更新用户名，需要检查唯一性
        if (isset($data['username']) && $data['username'] !== $user->username) {
            if (User::where('username', $data['username'])->where('id', '<>', $id)->count() > 0) {
                throw new ValidateException('用户名已存在');
            }
        }
        
        // 如果更新邮箱，需要检查唯一性
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->where('id', '<>', $id)->count() > 0) {
                throw new ValidateException('邮箱已存在');
            }
        }
        
        // 移除密码字段（密码不应该通过update方法更新）
        unset($data['password']);
        
        // 更新用户
        $user->save($data);
        
        return $user;
    }
    
    /**
     * 删除用户（软删除）
     * 验证需求: 2.4
     * 
     * @param int $id 用户ID
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            throw new \Exception('用户不存在');
        }
        
        // 软删除
        return $user->delete();
    }
    
    /**
     * 获取用户列表（分页）
     * 验证需求: 2.1, 2.6
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string|null $search 搜索关键词
     * @return \think\Paginator
     */
    public function getUserList(int $page = 1, int $pageSize = 10, ?string $search = null)
    {
        $query = User::withoutGlobalScope();
        
        // 搜索功能：支持按用户名、邮箱或手机号进行模糊搜索
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereLike('username', '%' . $search . '%')
                      ->whereOr('email', 'like', '%' . $search . '%')
                      ->whereOr('phone', 'like', '%' . $search . '%');
            });
        }
        
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
    }
    
    /**
     * 更新用户状态
     * 验证需求: 2.5
     * 
     * @param int $id 用户ID
     * @param int $status 状态（1:启用 0:禁用）
     * @return User
     * @throws \Exception
     */
    public function updateStatus(int $id, int $status)
    {
        $user = User::find($id);
        if (!$user) {
            throw new \Exception('用户不存在');
        }
        
        if (!in_array($status, [0, 1])) {
            throw new ValidateException('状态值无效');
        }
        
        $user->status = $status;
        $user->save();
        
        return $user;
    }
    
    /**
     * 根据ID获取用户
     * 
     * @param int $id 用户ID
     * @return User|null
     */
    public function getUserById(int $id)
    {
        return User::find($id);
    }
    
    /**
     * 根据用户名获取用户
     * 
     * @param string $username 用户名
     * @return User|null
     */
    public function getUserByUsername(string $username)
    {
        return User::where('username', $username)->find();
    }
}
