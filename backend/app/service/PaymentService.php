<?php

namespace app\service;

use app\model\Order;
use app\model\PaymentConfig;
use app\service\gateway\AlipayGateway;
use app\service\gateway\WechatGateway;
use app\service\gateway\PaypalGateway;
use app\service\gateway\PaymentGatewayInterface;

/**
 * 支付服务类
 * 处理支付相关业务逻辑
 * 验证需求: 9.1, 9.2, 9.3
 */
class PaymentService
{
    /**
     * 创建支付
     * 验证需求: 9.1, 9.2, 9.3
     * 
     * @param int $orderId 订单ID
     * @param string $gateway 支付网关
     * @return array 返回支付信息
     * @throws \Exception
     */
    public function createPayment(int $orderId, string $gateway): array
    {
        // 查找订单
        $order = Order::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        // 检查订单状态
        if (!$order->isPending()) {
            throw new \Exception('订单状态不正确');
        }
        
        // 获取支付网关实例
        $gatewayInstance = $this->getGateway($gateway);
        
        // 创建支付
        $paymentInfo = $gatewayInstance->createPayment($order);
        
        // 更新订单的支付网关
        $order->payment_gateway = $gateway;
        $order->save();
        
        return $paymentInfo;
    }
    
    /**
     * 处理支付回调
     * 验证需求: 9.1, 9.2, 9.3
     * 
     * @param string $gateway 支付网关
     * @param array $data 回调数据
     * @return Order 返回更新后的订单
     * @throws \Exception
     */
    public function handleCallback(string $gateway, array $data): Order
    {
        // 获取支付网关实例
        $gatewayInstance = $this->getGateway($gateway);
        
        // 处理回调
        $callbackResult = $gatewayInstance->handleCallback($data);
        
        // 查找订单
        $order = Order::where('order_no', $callbackResult['order_no'])->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        // 检查订单是否已支付
        if ($order->isPaid() || $order->isShipped() || $order->isCompleted()) {
            // 订单已支付，直接返回
            return $order;
        }
        
        // 更新订单状态
        $order->status = Order::STATUS_PAID;
        $order->paid_at = $callbackResult['paid_at'] ?? date('Y-m-d H:i:s');
        $order->save();
        
        return $order;
    }
    
    /**
     * 退款
     * 验证需求: 9.1, 9.2, 9.3
     * 
     * @param Order $order 订单对象
     * @return array 返回退款结果
     * @throws \Exception
     */
    public function refund(Order $order): array
    {
        // 检查订单是否已支付
        if (!$order->isPaid() && !$order->isShipped() && !$order->isCompleted()) {
            throw new \Exception('订单未支付，无需退款');
        }
        
        // 检查是否有支付网关信息
        if (empty($order->payment_gateway)) {
            throw new \Exception('订单没有支付网关信息');
        }
        
        // 获取支付网关实例
        $gatewayInstance = $this->getGateway($order->payment_gateway);
        
        // 执行退款
        return $gatewayInstance->refund($order);
    }
    
    /**
     * 获取支付网关实例
     * 
     * @param string $gateway 支付网关
     * @return PaymentGatewayInterface 支付网关实例
     * @throws \Exception
     */
    private function getGateway(string $gateway): PaymentGatewayInterface
    {
        // 获取支付配置
        $paymentConfig = PaymentConfig::where('gateway', $gateway)->find();
        if (!$paymentConfig) {
            throw new \Exception('支付配置不存在');
        }
        
        // 检查支付方式是否启用
        if ($paymentConfig->status !== 1) {
            throw new \Exception('支付方式未启用');
        }
        
        // 根据网关类型创建实例
        switch ($gateway) {
            case PaymentConfig::GATEWAY_ALIPAY:
                return new AlipayGateway($paymentConfig->config);
            case PaymentConfig::GATEWAY_WECHAT:
                return new WechatGateway($paymentConfig->config);
            case PaymentConfig::GATEWAY_PAYPAL:
                return new PaypalGateway($paymentConfig->config);
            default:
                throw new \Exception('不支持的支付方式');
        }
    }
    
    /**
     * 获取可用的支付方式列表
     * 验证需求: 9.5
     * 
     * @return array 返回启用的支付方式列表
     */
    public function getAvailableGateways(): array
    {
        $configs = PaymentConfig::where('status', 1)->select();
        
        $gateways = [];
        foreach ($configs as $config) {
            $gateways[] = [
                'gateway' => $config->gateway,
                'name' => $this->getGatewayName($config->gateway),
            ];
        }
        
        return $gateways;
    }
    
    /**
     * 获取支付网关名称
     * 
     * @param string $gateway 支付网关
     * @return string 网关名称
     */
    private function getGatewayName(string $gateway): string
    {
        $names = [
            PaymentConfig::GATEWAY_ALIPAY => '支付宝',
            PaymentConfig::GATEWAY_WECHAT => '微信支付',
            PaymentConfig::GATEWAY_PAYPAL => 'PayPal',
        ];
        
        return $names[$gateway] ?? $gateway;
    }
}
