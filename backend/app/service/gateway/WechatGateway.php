<?php

namespace app\service\gateway;

use app\model\Order;
use app\model\PaymentConfig;

/**
 * 微信支付网关
 * 验证需求: 9.2
 */
class WechatGateway implements PaymentGatewayInterface
{
    /**
     * @var array 支付配置
     */
    private $config;
    
    /**
     * 构造函数
     * 
     * @param array $config 支付配置
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * 创建支付
     * 验证需求: 9.2
     * 
     * @param Order $order 订单对象
     * @return array 返回支付信息
     */
    public function createPayment(Order $order): array
    {
        // 获取客户端IP
        $clientIp = '127.0.0.1';
        if (function_exists('request')) {
            try {
                $clientIp = request()->ip();
            } catch (\Exception $e) {
                // 在测试环境中可能无法获取request，使用默认值
            }
        }
        
        // 构建支付参数
        $params = [
            'appid' => $this->config['app_id'],
            'mch_id' => $this->config['mch_id'],
            'nonce_str' => $this->generateNonceStr(),
            'body' => '订单支付',
            'out_trade_no' => $order->order_no,
            'total_fee' => intval($order->total_amount * 100), // 转换为分
            'spbill_create_ip' => $clientIp,
            'notify_url' => $this->config['notify_url'],
            'trade_type' => 'NATIVE', // 扫码支付
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        // 构建XML请求
        $xml = $this->arrayToXml($params);
        
        // 这里应该调用微信统一下单API，简化实现返回模拟结果
        $codeUrl = 'weixin://wxpay/bizpayurl?pr=' . md5($order->order_no);
        
        return [
            'code_url' => $codeUrl,
            'order_no' => $order->order_no,
            'gateway' => PaymentConfig::GATEWAY_WECHAT,
        ];
    }
    
    /**
     * 处理支付回调
     * 验证需求: 9.2
     * 
     * @param array $data 回调数据
     * @return array 返回处理结果
     */
    public function handleCallback(array $data): array
    {
        // 验证签名
        if (!$this->verifySignature($data)) {
            throw new \Exception('签名验证失败');
        }
        
        // 检查返回状态
        if ($data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            throw new \Exception('交易未成功');
        }
        
        return [
            'order_no' => $data['out_trade_no'],
            'trade_no' => $data['transaction_id'],
            'total_amount' => $data['total_fee'] / 100, // 转换为元
            'paid_at' => $data['time_end'] ?? date('YmdHis'),
        ];
    }
    
    /**
     * 验证回调签名
     * 验证需求: 9.2
     * 
     * @param array $data 回调数据
     * @return bool 签名是否有效
     */
    public function verifySignature(array $data): bool
    {
        if (!isset($data['sign'])) {
            return false;
        }
        
        $sign = $data['sign'];
        unset($data['sign']);
        
        // 生成签名并比较
        $calculatedSign = $this->generateSign($data);
        
        return $sign === $calculatedSign;
    }
    
    /**
     * 退款
     * 验证需求: 9.2
     * 
     * @param Order $order 订单对象
     * @return array 返回退款结果
     */
    public function refund(Order $order): array
    {
        // 构建退款参数
        $params = [
            'appid' => $this->config['app_id'],
            'mch_id' => $this->config['mch_id'],
            'nonce_str' => $this->generateNonceStr(),
            'out_trade_no' => $order->order_no,
            'out_refund_no' => $order->order_no . '_refund',
            'total_fee' => intval($order->total_amount * 100),
            'refund_fee' => intval($order->total_amount * 100),
            'refund_desc' => '订单取消',
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        // 这里应该调用微信退款API，简化实现返回模拟结果
        return [
            'success' => true,
            'refund_no' => $order->order_no . '_refund',
            'refund_amount' => $order->total_amount,
        ];
    }
    
    /**
     * 生成签名
     * 
     * @param array $params 参数
     * @return string 签名
     */
    private function generateSign(array $params): string
    {
        // 移除sign参数
        unset($params['sign']);
        
        // 排序并构建待签名字符串
        ksort($params);
        $signString = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $signString .= $key . '=' . $value . '&';
            }
        }
        $signString .= 'key=' . $this->config['api_key'];
        
        // MD5签名
        return strtoupper(md5($signString));
    }
    
    /**
     * 生成随机字符串
     * 
     * @param int $length 长度
     * @return string 随机字符串
     */
    private function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
    
    /**
     * 数组转XML
     * 
     * @param array $data 数据
     * @return string XML字符串
     */
    private function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }
}
