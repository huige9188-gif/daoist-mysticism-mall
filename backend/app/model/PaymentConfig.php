<?php

namespace app\model;

use think\Model;

/**
 * 支付配置模型
 */
class PaymentConfig extends Model
{
    // 设置表名
    protected $name = 'payment_configs';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'config' => 'json',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // 支付网关常量
    const GATEWAY_ALIPAY = 'alipay';
    const GATEWAY_WECHAT = 'wechat';
    const GATEWAY_PAYPAL = 'paypal';
    
    /**
     * 获取所有支持的支付网关
     * @return array
     */
    public static function getSupportedGateways()
    {
        return [
            self::GATEWAY_ALIPAY,
            self::GATEWAY_WECHAT,
            self::GATEWAY_PAYPAL,
        ];
    }
    
    /**
     * 检查支付配置是否启用
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 1;
    }
    
    /**
     * 获取配置项
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfigValue($key, $default = null)
    {
        $config = $this->config;
        return $config[$key] ?? $default;
    }
}
