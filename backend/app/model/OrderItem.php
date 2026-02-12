<?php

namespace app\model;

use think\Model;

/**
 * 订单明细模型
 */
class OrderItem extends Model
{
    // 设置表名
    protected $name = 'order_items';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;  // 订单明细不需要更新时间
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'float',
        'created_at' => 'datetime',
    ];
    
    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo('Order', 'order_id', 'id');
    }
    
    /**
     * 关联商品
     */
    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id');
    }
    
    /**
     * 计算订单项小计
     * @return float
     */
    public function getSubtotal()
    {
        return $this->price * $this->quantity;
    }
}
