<?php

namespace tests\Order;

use PHPUnit\Framework\TestCase;
use app\model\Order;
use app\model\OrderItem;
use app\model\User;
use app\model\Product;

/**
 * 订单模型测试
 */
class OrderModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../bootstrap.php';
    }
    
    /**
     * 测试Order模型基本属性
     */
    public function testOrderModelBasicProperties()
    {
        $order = new Order();
        
        // 验证表名
        $this->assertEquals('orders', $order->getName());
        
        // 验证主键
        $this->assertEquals('id', $order->getPk());
    }
    
    /**
     * 测试OrderItem模型基本属性
     */
    public function testOrderItemModelBasicProperties()
    {
        $orderItem = new OrderItem();
        
        // 验证表名
        $this->assertEquals('order_items', $orderItem->getName());
        
        // 验证主键
        $this->assertEquals('id', $orderItem->getPk());
    }
    
    /**
     * 测试订单状态常量
     */
    public function testOrderStatusConstants()
    {
        $this->assertEquals('pending', Order::STATUS_PENDING);
        $this->assertEquals('paid', Order::STATUS_PAID);
        $this->assertEquals('shipped', Order::STATUS_SHIPPED);
        $this->assertEquals('completed', Order::STATUS_COMPLETED);
        $this->assertEquals('cancelled', Order::STATUS_CANCELLED);
    }
    
    /**
     * 测试订单状态检查方法
     */
    public function testOrderStatusCheckMethods()
    {
        $order = new Order();
        
        // 测试待支付状态
        $order->status = Order::STATUS_PENDING;
        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isPaid());
        
        // 测试已支付状态
        $order->status = Order::STATUS_PAID;
        $this->assertFalse($order->isPending());
        $this->assertTrue($order->isPaid());
        
        // 测试已发货状态
        $order->status = Order::STATUS_SHIPPED;
        $this->assertTrue($order->isShipped());
        
        // 测试已完成状态
        $order->status = Order::STATUS_COMPLETED;
        $this->assertTrue($order->isCompleted());
        
        // 测试已取消状态
        $order->status = Order::STATUS_CANCELLED;
        $this->assertTrue($order->isCancelled());
    }
    
    /**
     * 测试订单号生成
     */
    public function testGenerateOrderNo()
    {
        $orderNo1 = Order::generateOrderNo();
        $orderNo2 = Order::generateOrderNo();
        
        // 验证订单号格式（14位日期时间 + 4位随机数）
        $this->assertEquals(18, strlen($orderNo1));
        $this->assertMatchesRegularExpression('/^\d{18}$/', $orderNo1);
        
        // 验证两次生成的订单号不同（由于随机数）
        $this->assertNotEquals($orderNo1, $orderNo2);
    }
    
    /**
     * 测试OrderItem小计计算
     */
    public function testOrderItemSubtotalCalculation()
    {
        $orderItem = new OrderItem();
        $orderItem->price = 99.99;
        $orderItem->quantity = 3;
        
        $subtotal = $orderItem->getSubtotal();
        
        // 使用delta处理浮点数精度问题
        $this->assertEqualsWithDelta(299.97, $subtotal, 0.01);
    }
}
