<?php

namespace app\controller\api;

use app\BaseController;
use app\service\PaymentService;
use app\service\PaymentConfigService;
use think\Request;
use think\exception\ValidateException;

/**
 * 支付管理控制器
 * 
 * 验证需求: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6
 */
class Payment extends BaseController
{
    protected $paymentService;
    protected $paymentConfigService;
    
    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->paymentConfigService = new PaymentConfigService();
    }
    
    /**
     * 获取支付配置列表
     * GET /api/payment-configs
     * 
     * 验证需求: 9.1, 9.2, 9.3, 9.4
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function getConfigs(Request $request)
    {
        try {
            $configs = $this->paymentConfigService->getAllConfigs();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $configs
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    /**
     * 保存支付配置
     * POST /api/payment-configs
     * 
     * 验证需求: 9.1, 9.2, 9.3, 9.4
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function saveConfig(Request $request)
    {
        try {
            $data = $request->post();
            
            // 验证必填字段
            if (!isset($data['gateway']) || empty($data['gateway'])) {
                return json([
                    'code' => 400,
                    'message' => '支付网关不能为空',
                    'data' => null
                ], 400);
            }
            
            if (!isset($data['config']) || !is_array($data['config'])) {
                return json([
                    'code' => 400,
                    'message' => '配置信息格式不正确',
                    'data' => null
                ], 400);
            }
            
            $status = $data['status'] ?? 1;
            
            $config = $this->paymentConfigService->saveConfig(
                $data['gateway'],
                $data['config'],
                $status
            );
            
            return json([
                'code' => 200,
                'message' => '保存成功',
                'data' => $config
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    /**
     * 创建支付
     * POST /api/payments
     * 
     * 验证需求: 9.1, 9.2, 9.3, 9.5
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function createPayment(Request $request)
    {
        try {
            $data = $request->post();
            
            // 验证必填字段
            if (!isset($data['order_id']) || empty($data['order_id'])) {
                return json([
                    'code' => 400,
                    'message' => '订单ID不能为空',
                    'data' => null
                ], 400);
            }
            
            if (!isset($data['gateway']) || empty($data['gateway'])) {
                return json([
                    'code' => 400,
                    'message' => '支付网关不能为空',
                    'data' => null
                ], 400);
            }
            
            $paymentInfo = $this->paymentService->createPayment(
                $data['order_id'],
                $data['gateway']
            );
            
            return json([
                'code' => 200,
                'message' => '支付创建成功',
                'data' => $paymentInfo
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
    
    /**
     * 处理支付回调
     * POST /api/payments/callback
     * 
     * 验证需求: 9.1, 9.2, 9.3
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function handleCallback(Request $request)
    {
        try {
            $data = $request->post();
            
            // 验证必填字段
            if (!isset($data['gateway']) || empty($data['gateway'])) {
                return json([
                    'code' => 400,
                    'message' => '支付网关不能为空',
                    'data' => null
                ], 400);
            }
            
            $order = $this->paymentService->handleCallback(
                $data['gateway'],
                $data
            );
            
            return json([
                'code' => 200,
                'message' => '回调处理成功',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
    
    /**
     * 获取可用的支付方式列表
     * GET /api/payments/gateways
     * 
     * 验证需求: 9.5
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function getAvailableGateways(Request $request)
    {
        try {
            $gateways = $this->paymentService->getAvailableGateways();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $gateways
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
