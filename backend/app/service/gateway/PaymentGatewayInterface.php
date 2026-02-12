<?php

namespace app\service\gateway;

use app\model\Order;

/**
 * 支付网关接口
 * 定义所有支付网关必须实现的方法
 */
interface PaymentGatewayInterface
{
    /**
     * 创建支付
     * 
     * @param Order $order 订单对象
     * @return array 返回支付信息（包含支付URL或支付参数）
     */
    public function createPayment(Order $order): array;
    
    /**
     * 处理支付回调
     * 
     * @param array $data 回调数据
     * @return array 返回处理结果，包含订单ID和支付状态
     */
    public function handleCallback(array $data): array;
    
    /**
     * 验证回调签名
     * 
     * @param array $data 回调数据
     * @return bool 签名是否有效
     */
    public function verifySignature(array $data): bool;
    
    /**
     * 退款
     * 
     * @param Order $order 订单对象
     * @return array 返回退款结果
     */
    public function refund(Order $order): array;
}
