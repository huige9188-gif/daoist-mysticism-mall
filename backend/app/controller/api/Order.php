<?php

namespace app\controller\api;

use app\BaseController;
use app\service\OrderService;
use think\Request;
use think\exception\ValidateException;

/**
 * 订单管理控制器
 * 
 * 验证需求: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7, 5.8
 */
class Order extends BaseController
{
    protected $orderService;
    
    public function __construct()
    {
        $this->orderService = new OrderService();
    }
    
    /**
     * 获取订单列表（支持搜索和筛选）
     * GET /api/orders
     * 
     * 验证需求: 5.1, 5.7, 5.8
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        
        // 构建筛选条件
        $filters = [];
        
        // 按订单号搜索
        if ($request->has('order_no')) {
            $filters['order_no'] = $request->get('order_no');
        }
        
        // 按用户ID筛选
        if ($request->has('user_id')) {
            $filters['user_id'] = $request->get('user_id');
        }
        
        // 按订单状态筛选
        if ($request->has('status')) {
            $filters['status'] = $request->get('status');
        }
        
        // 按日期范围筛选
        if ($request->has('start_date')) {
            $filters['start_date'] = $request->get('start_date');
        }
        
        if ($request->has('end_date')) {
            $filters['end_date'] = $request->get('end_date');
        }
        
        $orders = $this->orderService->getOrderList($page, $pageSize, $filters);
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $orders
        ]);
    }
    
    /**
     * 获取订单详情
     * GET /api/orders/:id
     * 
     * 验证需求: 5.2
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function read(Request $request, $id)
    {
        try {
            $order = $this->orderService->getOrderById($id);
            
            if (!$order) {
                return json([
                    'code' => 404,
                    'message' => '订单不存在',
                    'data' => null
                ], 404);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $order
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
     * 创建订单
     * POST /api/orders
     * 
     * 验证需求: 5.5
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            // 验证必填字段
            if (!isset($data['items']) || empty($data['items'])) {
                return json([
                    'code' => 400,
                    'message' => '订单商品列表不能为空',
                    'data' => null
                ], 400);
            }
            
            if (!isset($data['address']) || empty($data['address'])) {
                return json([
                    'code' => 400,
                    'message' => '收货地址不能为空',
                    'data' => null
                ], 400);
            }
            
            // 获取当前用户ID（从认证中间件注入）
            $userId = $request->user['id'] ?? null;
            
            if (!$userId) {
                return json([
                    'code' => 401,
                    'message' => '未授权访问',
                    'data' => null
                ], 401);
            }
            
            $order = $this->orderService->createOrder(
                $userId,
                $data['items'],
                $data['address']
            );
            
            return json([
                'code' => 200,
                'message' => '订单创建成功',
                'data' => $order
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
     * 订单发货
     * POST /api/orders/:id/ship
     * 
     * 验证需求: 5.3, 5.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function ship(Request $request, $id)
    {
        try {
            $data = $request->post();
            
            // 验证物流信息
            if (!isset($data['logistics_company']) || empty($data['logistics_company'])) {
                return json([
                    'code' => 400,
                    'message' => '物流公司不能为空',
                    'data' => null
                ], 400);
            }
            
            if (!isset($data['logistics_number']) || empty($data['logistics_number'])) {
                return json([
                    'code' => 400,
                    'message' => '物流单号不能为空',
                    'data' => null
                ], 400);
            }
            
            $logistics = [
                'company' => $data['logistics_company'],
                'number' => $data['logistics_number']
            ];
            
            $order = $this->orderService->shipOrder($id, $logistics);
            
            return json([
                'code' => 200,
                'message' => '发货成功',
                'data' => $order
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
    
    /**
     * 取消订单
     * POST /api/orders/:id/cancel
     * 
     * 验证需求: 5.5, 5.6
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function cancel(Request $request, $id)
    {
        try {
            $order = $this->orderService->cancelOrder($id);
            
            return json([
                'code' => 200,
                'message' => '订单取消成功',
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
}
