<?php

namespace app\service;

use app\model\Order;
use app\model\OrderItem;
use app\model\Product;
use think\facade\Db;
use think\exception\ValidateException;

/**
 * 订单服务类
 * 处理订单相关业务逻辑
 * 验证需求: 5.3, 5.4, 5.5, 5.6
 */
class OrderService
{
    /**
     * 创建订单
     * 验证需求: 5.5
     * 
     * 包含库存扣减和事务处理
     * 
     * @param int $userId 用户ID
     * @param array $items 订单商品列表 [['product_id' => 1, 'quantity' => 2], ...]
     * @param array $address 收货地址
     * @return Order
     * @throws \Exception
     */
    public function createOrder(int $userId, array $items, array $address)
    {
        // 验证订单商品列表不能为空
        if (empty($items)) {
            throw new ValidateException('订单商品列表不能为空');
        }
        
        // 开始事务
        Db::startTrans();
        
        try {
            $totalAmount = 0;
            $orderItems = [];
            
            // 遍历订单商品，检查库存并计算总金额
            foreach ($items as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity'])) {
                    throw new ValidateException('订单商品数据不完整');
                }
                
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                
                // 验证数量必须大于0
                if ($quantity <= 0) {
                    throw new ValidateException('商品数量必须大于0');
                }
                
                // 查询商品
                $product = Product::find($productId);
                if (!$product) {
                    throw new \Exception('商品不存在');
                }
                
                // 检查库存是否充足
                if ($product->stock < $quantity) {
                    throw new \Exception("商品「{$product->name}」库存不足");
                }
                
                // 计算小计
                $subtotal = $product->price * $quantity;
                $totalAmount += $subtotal;
                
                // 减少库存
                $product->stock -= $quantity;
                $product->save();
                
                // 保存订单商品信息
                $orderItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ];
            }
            
            // 生成订单号
            $orderNo = Order::generateOrderNo();
            
            // 创建订单
            $order = Order::create([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'status' => Order::STATUS_PENDING,
                'address' => $address,
            ]);
            
            // 创建订单明细
            foreach ($orderItems as $orderItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $orderItem['product_id'],
                    'product_name' => $orderItem['product_name'],
                    'quantity' => $orderItem['quantity'],
                    'price' => $orderItem['price'],
                ]);
            }
            
            // 提交事务
            Db::commit();
            
            // 重新查询订单以获取关联数据
            return Order::with(['items'])->find($order->id);
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 发货
     * 验证需求: 5.3, 5.4
     * 
     * @param int $orderId 订单ID
     * @param array $logistics 物流信息 ['company' => '顺丰', 'number' => 'SF1234567890']
     * @return Order
     * @throws \Exception
     */
    public function shipOrder(int $orderId, array $logistics)
    {
        // 查询订单
        $order = Order::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        // 验证订单状态必须是已支付
        if ($order->status !== Order::STATUS_PAID) {
            throw new \Exception('订单状态不正确，只有已支付订单才能发货');
        }
        
        // 验证物流公司必填
        if (empty($logistics['company'])) {
            throw new ValidateException('物流公司不能为空');
        }
        
        // 验证物流单号必填
        if (empty($logistics['number'])) {
            throw new ValidateException('物流单号不能为空');
        }
        
        // 更新订单状态和物流信息
        $order->status = Order::STATUS_SHIPPED;
        $order->logistics_company = $logistics['company'];
        $order->logistics_number = $logistics['number'];
        $order->shipped_at = date('Y-m-d H:i:s');
        $order->save();
        
        return $order;
    }
    
    /**
     * 取消订单
     * 验证需求: 5.5, 5.6
     * 
     * 包含库存恢复和退款处理
     * 
     * @param int $orderId 订单ID
     * @return Order
     * @throws \Exception
     */
    public function cancelOrder(int $orderId)
    {
        // 查询订单
        $order = Order::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        // 验证订单状态（已完成和已取消的订单不能取消）
        if ($order->status === Order::STATUS_COMPLETED) {
            throw new \Exception('订单已完成，无法取消');
        }
        
        if ($order->status === Order::STATUS_CANCELLED) {
            throw new \Exception('订单已取消');
        }
        
        // 开始事务
        Db::startTrans();
        
        try {
            // 恢复库存
            $items = OrderItem::where('order_id', $orderId)->select();
            foreach ($items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock += $item->quantity;
                    $product->save();
                }
            }
            
            // 更新订单状态
            $order->status = Order::STATUS_CANCELLED;
            $order->save();
            
            // 如果订单已支付，触发退款
            if ($order->paid_at) {
                // 这里应该调用支付服务的退款方法
                // PaymentService::refund($order);
                // 由于支付服务尚未实现，这里只做标记
                // 实际项目中需要调用支付网关的退款接口
            }
            
            // 提交事务
            Db::commit();
            
            return $order;
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 根据ID获取订单
     * 
     * @param int $orderId 订单ID
     * @return Order|null
     */
    public function getOrderById(int $orderId)
    {
        return Order::with(['items', 'user'])->find($orderId);
    }
    
    /**
     * 获取订单列表（分页）
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param array $filters 筛选条件
     * @return \think\Paginator
     */
    public function getOrderList(int $page = 1, int $pageSize = 10, array $filters = [])
    {
        $query = Order::with(['user', 'items']);
        
        // 按订单号搜索
        if (!empty($filters['order_no'])) {
            $query->where('order_no', 'like', '%' . $filters['order_no'] . '%');
        }
        
        // 按用户ID筛选
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        // 按订单状态筛选
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // 按日期范围筛选
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        // 按创建时间降序排列
        $query->order('created_at', 'desc');
        
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
    }
}
