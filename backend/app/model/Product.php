<?php

namespace app\model;

use think\Model;

/**
 * 商品模型
 */
class Product extends Model
{
    // 设置表名
    protected $name = 'products';
    
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
        'category_id' => 'integer',
        'price' => 'float',
        'stock' => 'integer',
        'images' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * 检查商品是否上架
     * @return bool
     */
    public function isOnSale()
    {
        return $this->status === 'on_sale';
    }
    
    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo('Category', 'category_id', 'id');
    }
}
