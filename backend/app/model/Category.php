<?php

namespace app\model;

use think\Model;

/**
 * 商品分类模型
 */
class Category extends Model
{
    // 设置表名
    protected $name = 'categories';
    
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
    protected $hidden = ['deleted_at'];
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * 检查分类是否启用
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 1;
    }
    
    /**
     * 关联商品
     */
    public function products()
    {
        return $this->hasMany('Product', 'category_id', 'id');
    }
}
