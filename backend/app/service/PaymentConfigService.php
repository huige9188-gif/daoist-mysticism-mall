<?php

namespace app\service;

use app\model\PaymentConfig;
use think\exception\ValidateException;

/**
 * 支付配置服务类
 * 处理支付配置相关业务逻辑
 * 验证需求: 9.1, 9.2, 9.3, 9.4
 */
class PaymentConfigService
{
    /**
     * 支付宝必填字段
     */
    private const ALIPAY_REQUIRED_FIELDS = ['app_id', 'private_key', 'public_key', 'notify_url'];
    
    /**
     * 微信支付必填字段
     */
    private const WECHAT_REQUIRED_FIELDS = ['app_id', 'mch_id', 'api_key', 'notify_url'];
    
    /**
     * PayPal必填字段
     */
    private const PAYPAL_REQUIRED_FIELDS = ['client_id', 'secret', 'notify_url'];
    
    /**
     * 创建或更新支付配置
     * 验证需求: 9.1, 9.2, 9.3, 9.4
     * 
     * @param string $gateway 支付网关
     * @param array $config 配置信息
     * @param int $status 状态
     * @return PaymentConfig
     * @throws ValidateException
     */
    public function saveConfig(string $gateway, array $config, int $status = 1)
    {
        // 验证支付网关是否支持
        if (!in_array($gateway, PaymentConfig::getSupportedGateways())) {
            throw new ValidateException('不支持的支付网关');
        }
        
        // 验证状态值
        if (!in_array($status, [0, 1])) {
            throw new ValidateException('状态值无效');
        }
        
        // 验证必填字段
        $this->validateRequiredFields($gateway, $config);
        
        // 查找是否已存在该网关的配置
        $paymentConfig = PaymentConfig::where('gateway', $gateway)->find();
        
        if ($paymentConfig) {
            // 更新现有配置
            $paymentConfig->config = $config;
            $paymentConfig->status = $status;
            $paymentConfig->save();
        } else {
            // 创建新配置
            $paymentConfig = PaymentConfig::create([
                'gateway' => $gateway,
                'config' => $config,
                'status' => $status,
            ]);
        }
        
        return $paymentConfig;
    }
    
    /**
     * 验证必填字段
     * 验证需求: 9.1, 9.2, 9.3, 9.4
     * 
     * @param string $gateway 支付网关
     * @param array $config 配置信息
     * @throws ValidateException
     */
    private function validateRequiredFields(string $gateway, array $config)
    {
        $requiredFields = [];
        
        switch ($gateway) {
            case PaymentConfig::GATEWAY_ALIPAY:
                // 验证需求: 9.1
                $requiredFields = self::ALIPAY_REQUIRED_FIELDS;
                break;
            case PaymentConfig::GATEWAY_WECHAT:
                // 验证需求: 9.2
                $requiredFields = self::WECHAT_REQUIRED_FIELDS;
                break;
            case PaymentConfig::GATEWAY_PAYPAL:
                // 验证需求: 9.3
                $requiredFields = self::PAYPAL_REQUIRED_FIELDS;
                break;
        }
        
        // 检查必填字段
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new ValidateException('缺少必填字段: ' . implode(', ', $missingFields));
        }
    }
    
    /**
     * 获取支付配置
     * 
     * @param string $gateway 支付网关
     * @return PaymentConfig|null
     */
    public function getConfig(string $gateway)
    {
        return PaymentConfig::where('gateway', $gateway)->find();
    }
    
    /**
     * 获取所有支付配置
     * 
     * @return array
     */
    public function getAllConfigs()
    {
        return PaymentConfig::select()->toArray();
    }
    
    /**
     * 获取所有启用的支付配置
     * 验证需求: 9.5
     * 
     * @return array
     */
    public function getActiveConfigs()
    {
        return PaymentConfig::where('status', 1)->select()->toArray();
    }
    
    /**
     * 更新支付配置状态
     * 验证需求: 9.5, 9.6
     * 
     * @param string $gateway 支付网关
     * @param int $status 状态（1:启用 0:禁用）
     * @return PaymentConfig
     * @throws \Exception
     */
    public function updateStatus(string $gateway, int $status)
    {
        $paymentConfig = PaymentConfig::where('gateway', $gateway)->find();
        if (!$paymentConfig) {
            throw new \Exception('支付配置不存在');
        }
        
        if (!in_array($status, [0, 1])) {
            throw new ValidateException('状态值无效');
        }
        
        $paymentConfig->status = $status;
        $paymentConfig->save();
        
        return $paymentConfig;
    }
    
    /**
     * 删除支付配置
     * 
     * @param string $gateway 支付网关
     * @return bool
     * @throws \Exception
     */
    public function deleteConfig(string $gateway)
    {
        $paymentConfig = PaymentConfig::where('gateway', $gateway)->find();
        if (!$paymentConfig) {
            throw new \Exception('支付配置不存在');
        }
        
        return $paymentConfig->delete();
    }
}
