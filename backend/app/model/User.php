<?php

namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    // 设置表名
    protected $name = 'users';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = 'deleted_at';
    
    // 软删除
    use \think\model\concern\SoftDelete;
    
    // 隐藏字段
    protected $hidden = ['password', 'deleted_at'];
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * 密码修改器 - 自动加密密码
     */
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
    
    /**
     * 验证密码
     * @param string $password 明文密码
     * @return bool
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }
    
    /**
     * 检查用户是否为管理员
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * 检查用户是否启用
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 1;
    }
}
