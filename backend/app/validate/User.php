<?php

namespace app\validate;

use think\Validate;

/**
 * 用户验证器
 * 验证需求: 2.2
 */
class User extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'username' => 'require|alphaNum|length:3,50',
        'password' => 'require|length:6,255',
        'email'    => 'require|email|length:1,100',
        'phone'    => 'mobile',
        'role'     => 'in:admin,user',
        'status'   => 'in:0,1',
    ];

    /**
     * 验证消息
     */
    protected $message = [
        'username.require'   => '用户名不能为空',
        'username.alphaNum'  => '用户名只能包含字母和数字',
        'username.length'    => '用户名长度必须在3-50个字符之间',
        'username.unique'    => '用户名已存在',
        'password.require'   => '密码不能为空',
        'password.length'    => '密码长度必须在6-255个字符之间',
        'email.require'      => '邮箱不能为空',
        'email.email'        => '邮箱格式不正确',
        'email.length'       => '邮箱长度不能超过100个字符',
        'email.unique'       => '邮箱已存在',
        'phone.mobile'       => '手机号格式不正确',
        'role.in'            => '角色必须是admin或user',
        'status.in'          => '状态必须是0或1',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'create' => ['username', 'password', 'email', 'phone', 'role', 'status'],
        'update' => ['username', 'email', 'phone', 'role', 'status'],
        'login'  => ['username', 'password'],
    ];

    /**
     * 更新场景下的唯一性验证需要排除当前记录
     */
    public function sceneUpdate()
    {
        return $this->remove('password', 'require')
                    ->remove('username', 'unique')
                    ->remove('email', 'unique');
    }
}
