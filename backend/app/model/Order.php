<?php

namespace app\model;

use think\Model;

/**
 * 订单模型
 */
class Order extends Model
{
    // 设置表名
    protected $name = 'orders';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'total_amount' => 'float',
        'address' => 'json',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // 订单状态常量
    const STATUS_PENDING = 'pending';      // 待支付
    const STATUS_PAID = 'paid';            // 已支付/待发货
    const STATUS_SHIPPED = 'shipped';      // 已发货
    const STATUS_COMPLETED = 'completed';  // 已完成
    const STATUS_CANCELLED = 'cancelled';  // 已取消
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }
    
    /**
     * 关联订单明细
     */
    public function items()
    {
        return $this->hasMany('OrderItem', 'order_id', 'id');
    }
    
    /**
     * 检查订单是否待支付
     * @return bool
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    /**
     * 检查订单是否已支付
     * @return bool
     */
    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }
    
    /**
     * 检查订单是否已发货
     * @return bool
     */
    public function isShipped()
    {
        return $this->status === self::STATUS_SHIPPED;
    }
    
    /**
     * 检查订单是否已完成
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    /**
     * 检查订单是否已取消
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }
    
    /**
     * 生成订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return date('YmdHis') . rand(1000, 9999);
    }
}
