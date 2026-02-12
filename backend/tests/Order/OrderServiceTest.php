<?php

namespace Tests\Order;

use PHPUnit\Framework\TestCase;
use app\model\Order;
use app\model\OrderItem;
use app\model\Product;
use app\model\Category;
use app\model\User;
use app\service\OrderService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 订单服务测试
 * 
 * 验证需求: 5.3, 5.4, 5.5, 5.6
 */
class OrderServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private OrderService $service;
    private ?User $testUser = null;
    private ?Category $testCategory = null;
    private ?Product $testProduct1 = null;
    private ?Product $testProduct2 = null;
    
    public static function setUpBeforeClass(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'daoist_mall_test';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '123456';

        try {
            self::$pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (\PDOException $e) {
            self::fail("无法连接到数据库: " . $e->getMessage());
        }
        
        // 初始化ThinkPHP数据库连接
        $config = [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'type' => 'mysql',
                    'hostname' => $host,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'hostport' => $port,
                    'charset' => 'utf8mb4',
                    'prefix' => '',
                ],
            ],
        ];
        
        Db::setConfig($config);
    }
    
    protected function setUp(): void
    {
        $this->service = new OrderService();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空相关表
        self::$pdo->exec("TRUNCATE TABLE order_items");
        self::$pdo->exec("TRUNCATE TABLE orders");
        self::$pdo->exec("TRUNCATE TABLE products");
        self::$pdo->exec("TRUNCATE TABLE categories");
        self::$pdo->exec("TRUNCATE TABLE users");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // 创建测试用户
        $this->testUser = User::create([
            'username' => 'testuser',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'email' => 'test@example.com',
            'phone' => '13800138000',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 创建测试分类
        $this->testCategory = Category::create([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 创建测试商品
        $this->testProduct1 = Product::create([
            'category_id' => $this->testCategory->id,
            'name' => '五帝钱',
            'description' => '招财辟邪',
            'price' => 88.00,
            'stock' => 100,
            'status' => 'on_sale'
        ]);
        
        $this->testProduct2 = Product::create([
            'category_id' => $this->testCategory->id,
            'name' => '桃木剑',
            'description' => '镇宅辟邪',
            'price' => 168.00,
            'stock' => 50,
            'status' => 'on_sale'
        ]);
    }
    
    /**
     * 测试创建订单成功
     * 验证需求: 5.5
     */
    public function testCreateOrderSuccess(): void
    {
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 2],
            ['product_id' => $this->testProduct2->id, 'quantity' => 1],
        ];
        
        $address = [
            'name' => '张三',
            'phone' => '13800138000',
            'province' => '广东省',
            'city' => '深圳市',
            'district' => '南山区',
            'detail' => '科技园'
        ];
        
        $order = $this->service->createOrder($this->testUser->id, $items, $address);
        
        // 验证订单创建成功
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($this->testUser->id, $order->user_id);
        $this->assertEquals(344.00, $order->total_amount); // 88*2 + 168*1
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
        $this->assertNotEmpty($order->order_no);
        
        // 验证订单明细
        $this->assertCount(2, $order->items);
        
        // 验证库存扣减
        $product1 = Product::find($this->testProduct1->id);
        $product2 = Product::find($this->testProduct2->id);
        $this->assertEquals(98, $product1->stock); // 100 - 2
        $this->assertEquals(49, $product2->stock); // 50 - 1
    }
    
    /**
     * 测试创建订单时商品列表为空
     * 验证需求: 5.5
     */
    public function testCreateOrderWithEmptyItems(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('订单商品列表不能为空');
        
        $this->service->createOrder($this->testUser->id, [], []);
    }
    
    /**
     * 测试创建订单时商品数据不完整
     * 验证需求: 5.5
     */
    public function testCreateOrderWithIncompleteItemData(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('订单商品数据不完整');
        
        $items = [
            ['product_id' => $this->testProduct1->id], // 缺少quantity
        ];
        
        $this->service->createOrder($this->testUser->id, $items, []);
    }
    
    /**
     * 测试创建订单时商品数量为零
     * 验证需求: 5.5
     */
    public function testCreateOrderWithZeroQuantity(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('商品数量必须大于0');
        
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 0],
        ];
        
        $this->service->createOrder($this->testUser->id, $items, []);
    }
    
    /**
     * 测试创建订单时商品不存在
     * 验证需求: 5.5
     */
    public function testCreateOrderWithNonExistentProduct(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('商品不存在');
        
        $items = [
            ['product_id' => 99999, 'quantity' => 1],
        ];
        
        $this->service->createOrder($this->testUser->id, $items, []);
    }
    
    /**
     * 测试创建订单时库存不足
     * 验证需求: 5.5
     */
    public function testCreateOrderWithInsufficientStock(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/库存不足/');
        
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 200], // 库存只有100
        ];
        
        $this->service->createOrder($this->testUser->id, $items, []);
    }
    
    /**
     * 测试创建订单失败时库存回滚
     * 验证需求: 5.5
     */
    public function testCreateOrderRollbackOnFailure(): void
    {
        $initialStock = $this->testProduct1->stock;
        
        try {
            $items = [
                ['product_id' => $this->testProduct1->id, 'quantity' => 2],
                ['product_id' => 99999, 'quantity' => 1], // 不存在的商品
            ];
            
            $this->service->createOrder($this->testUser->id, $items, []);
        } catch (\Exception $e) {
            // 验证库存未被扣减
            $product = Product::find($this->testProduct1->id);
            $this->assertEquals($initialStock, $product->stock);
        }
    }
    
    /**
     * 测试发货成功
     * 验证需求: 5.3, 5.4
     */
    public function testShipOrderSuccess(): void
    {
        // 创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 模拟支付
        $order->status = Order::STATUS_PAID;
        $order->paid_at = date('Y-m-d H:i:s');
        $order->save();
        
        // 发货
        $logistics = [
            'company' => '顺丰速运',
            'number' => 'SF1234567890'
        ];
        
        $shippedOrder = $this->service->shipOrder($order->id, $logistics);
        
        // 验证订单状态
        $this->assertEquals(Order::STATUS_SHIPPED, $shippedOrder->status);
        $this->assertEquals('顺丰速运', $shippedOrder->logistics_company);
        $this->assertEquals('SF1234567890', $shippedOrder->logistics_number);
        $this->assertNotNull($shippedOrder->shipped_at);
    }
    
    /**
     * 测试发货时订单不存在
     * 验证需求: 5.3
     */
    public function testShipOrderNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单不存在');
        
        $logistics = [
            'company' => '顺丰速运',
            'number' => 'SF1234567890'
        ];
        
        $this->service->shipOrder(99999, $logistics);
    }
    
    /**
     * 测试发货时订单状态不正确
     * 验证需求: 5.3
     */
    public function testShipOrderWithWrongStatus(): void
    {
        // 创建订单（待支付状态）
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单状态不正确');
        
        $logistics = [
            'company' => '顺丰速运',
            'number' => 'SF1234567890'
        ];
        
        $this->service->shipOrder($order->id, $logistics);
    }
    
    /**
     * 测试发货时缺少物流公司
     * 验证需求: 5.4
     */
    public function testShipOrderWithoutCompany(): void
    {
        // 创建并支付订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        $order->status = Order::STATUS_PAID;
        $order->paid_at = date('Y-m-d H:i:s');
        $order->save();
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('物流公司不能为空');
        
        $logistics = [
            'number' => 'SF1234567890'
        ];
        
        $this->service->shipOrder($order->id, $logistics);
    }
    
    /**
     * 测试发货时缺少物流单号
     * 验证需求: 5.4
     */
    public function testShipOrderWithoutNumber(): void
    {
        // 创建并支付订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        $order->status = Order::STATUS_PAID;
        $order->paid_at = date('Y-m-d H:i:s');
        $order->save();
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('物流单号不能为空');
        
        $logistics = [
            'company' => '顺丰速运'
        ];
        
        $this->service->shipOrder($order->id, $logistics);
    }
    
    /**
     * 测试取消订单成功
     * 验证需求: 5.5
     */
    public function testCancelOrderSuccess(): void
    {
        // 创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 5],
            ['product_id' => $this->testProduct2->id, 'quantity' => 3],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 记录扣减后的库存
        $product1StockAfterOrder = Product::find($this->testProduct1->id)->stock;
        $product2StockAfterOrder = Product::find($this->testProduct2->id)->stock;
        
        // 取消订单
        $cancelledOrder = $this->service->cancelOrder($order->id);
        
        // 验证订单状态
        $this->assertEquals(Order::STATUS_CANCELLED, $cancelledOrder->status);
        
        // 验证库存恢复
        $product1 = Product::find($this->testProduct1->id);
        $product2 = Product::find($this->testProduct2->id);
        $this->assertEquals($product1StockAfterOrder + 5, $product1->stock);
        $this->assertEquals($product2StockAfterOrder + 3, $product2->stock);
    }
    
    /**
     * 测试取消订单时订单不存在
     * 验证需求: 5.5
     */
    public function testCancelOrderNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单不存在');
        
        $this->service->cancelOrder(99999);
    }
    
    /**
     * 测试取消已完成的订单
     * 验证需求: 5.5
     */
    public function testCancelCompletedOrder(): void
    {
        // 创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 设置为已完成状态
        $order->status = Order::STATUS_COMPLETED;
        $order->completed_at = date('Y-m-d H:i:s');
        $order->save();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单已完成，无法取消');
        
        $this->service->cancelOrder($order->id);
    }
    
    /**
     * 测试取消已取消的订单
     * 验证需求: 5.5
     */
    public function testCancelAlreadyCancelledOrder(): void
    {
        // 创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 第一次取消
        $this->service->cancelOrder($order->id);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单已取消');
        
        // 第二次取消
        $this->service->cancelOrder($order->id);
    }
    
    /**
     * 测试取消已支付订单（应触发退款）
     * 验证需求: 5.6
     */
    public function testCancelPaidOrder(): void
    {
        // 创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 2],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 模拟支付
        $order->status = Order::STATUS_PAID;
        $order->paid_at = date('Y-m-d H:i:s');
        $order->save();
        
        // 取消订单
        $cancelledOrder = $this->service->cancelOrder($order->id);
        
        // 验证订单状态
        $this->assertEquals(Order::STATUS_CANCELLED, $cancelledOrder->status);
        
        // 注意：实际的退款逻辑需要在PaymentService中实现
        // 这里只验证订单状态变更和库存恢复
    }
    
    /**
     * 测试获取订单详情
     */
    public function testGetOrderById(): void
    {
        // 创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        $order = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 获取订单详情
        $fetchedOrder = $this->service->getOrderById($order->id);
        
        $this->assertNotNull($fetchedOrder);
        $this->assertEquals($order->id, $fetchedOrder->id);
        $this->assertNotNull($fetchedOrder->items);
        $this->assertNotNull($fetchedOrder->user);
    }
    
    /**
     * 测试获取订单列表
     */
    public function testGetOrderList(): void
    {
        // 创建多个订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        
        $this->service->createOrder($this->testUser->id, $items, []);
        $this->service->createOrder($this->testUser->id, $items, []);
        $this->service->createOrder($this->testUser->id, $items, []);
        
        // 获取订单列表
        $result = $this->service->getOrderList(1, 10);
        
        $this->assertCount(3, $result->items());
    }
    
    /**
     * 测试按用户ID筛选订单
     */
    public function testGetOrderListByUserId(): void
    {
        // 创建另一个用户
        $user2 = User::create([
            'username' => 'testuser2',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'email' => 'test2@example.com',
            'phone' => '13800138001',
            'role' => 'user',
            'status' => 1
        ]);
        
        // 为不同用户创建订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        
        $this->service->createOrder($this->testUser->id, $items, []);
        $this->service->createOrder($this->testUser->id, $items, []);
        $this->service->createOrder($user2->id, $items, []);
        
        // 按用户ID筛选
        $result = $this->service->getOrderList(1, 10, ['user_id' => $this->testUser->id]);
        
        $this->assertCount(2, $result->items());
    }
    
    /**
     * 测试按订单状态筛选
     */
    public function testGetOrderListByStatus(): void
    {
        // 创建不同状态的订单
        $items = [
            ['product_id' => $this->testProduct1->id, 'quantity' => 1],
        ];
        
        $order1 = $this->service->createOrder($this->testUser->id, $items, []);
        $order2 = $this->service->createOrder($this->testUser->id, $items, []);
        
        // 设置一个订单为已支付
        $order2->status = Order::STATUS_PAID;
        $order2->paid_at = date('Y-m-d H:i:s');
        $order2->save();
        
        // 按状态筛选
        $result = $this->service->getOrderList(1, 10, ['status' => Order::STATUS_PENDING]);
        
        $this->assertCount(1, $result->items());
        $this->assertEquals(Order::STATUS_PENDING, $result->items()[0]->status);
    }
}
