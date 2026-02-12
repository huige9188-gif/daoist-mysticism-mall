<?php

namespace Tests\Statistics;

use PHPUnit\Framework\TestCase;
use app\model\Order;
use app\model\OrderItem;
use app\model\Product;
use app\model\Category;
use app\model\User;
use app\service\StatisticsService;
use think\facade\Db;
use PDO;

/**
 * 统计服务测试
 * 
 * 验证需求: 1.1, 1.2, 1.3
 */
class StatisticsServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private StatisticsService $service;
    
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
        $this->service = new StatisticsService();
        
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
    }
    
    /**
     * 测试获取总订单数
     * 验证需求: 1.1
     */
    public function testGetTotalOrders(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        $this->createTestOrder($user->id, 100.00, Order::STATUS_PENDING);
        $this->createTestOrder($user->id, 200.00, Order::STATUS_COMPLETED);
        $this->createTestOrder($user->id, 150.00, Order::STATUS_CANCELLED);
        
        $totalOrders = $this->service->getTotalOrders();
        
        $this->assertEquals(3, $totalOrders);
    }
    
    /**
     * 测试获取总销售额
     * 验证需求: 1.1
     */
    public function testGetTotalSales(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        $this->createTestOrder($user->id, 100.00, Order::STATUS_COMPLETED);
        $this->createTestOrder($user->id, 200.00, Order::STATUS_COMPLETED);
        $this->createTestOrder($user->id, 150.00, Order::STATUS_PENDING); // 未完成，不计入销售额
        $this->createTestOrder($user->id, 80.00, Order::STATUS_CANCELLED); // 已取消，不计入销售额
        
        $totalSales = $this->service->getTotalSales();
        
        $this->assertEquals(300.00, $totalSales);
    }
    
    /**
     * 测试获取总用户数
     * 验证需求: 1.1
     */
    public function testGetTotalUsers(): void
    {
        // 创建测试用户
        $this->createTestUser('user1', 'user1@test.com');
        $this->createTestUser('user2', 'user2@test.com');
        $this->createTestUser('user3', 'user3@test.com');
        
        $totalUsers = $this->service->getTotalUsers();
        
        $this->assertEquals(3, $totalUsers);
    }
    
    /**
     * 测试获取总商品数
     * 验证需求: 1.1
     */
    public function testGetTotalProducts(): void
    {
        // 创建测试分类
        $category = $this->createTestCategory();
        
        // 创建测试商品
        $this->createTestProduct($category->id, '商品1', 100.00);
        $this->createTestProduct($category->id, '商品2', 200.00);
        $this->createTestProduct($category->id, '商品3', 150.00);
        $this->createTestProduct($category->id, '商品4', 80.00);
        
        $totalProducts = $this->service->getTotalProducts();
        
        $this->assertEquals(4, $totalProducts);
    }
    
    /**
     * 测试获取订单状态统计
     * 验证需求: 1.2
     */
    public function testGetOrderStatusCounts(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        
        // 创建不同状态的订单
        $this->createTestOrder($user->id, 100.00, Order::STATUS_PENDING);
        $this->createTestOrder($user->id, 200.00, Order::STATUS_PENDING);
        $this->createTestOrder($user->id, 150.00, Order::STATUS_PAID);
        $this->createTestOrder($user->id, 180.00, Order::STATUS_SHIPPED);
        $this->createTestOrder($user->id, 220.00, Order::STATUS_SHIPPED);
        $this->createTestOrder($user->id, 300.00, Order::STATUS_COMPLETED);
        $this->createTestOrder($user->id, 120.00, Order::STATUS_CANCELLED);
        
        $statusCounts = $this->service->getOrderStatusCounts();
        
        $this->assertEquals(2, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['paid']);
        $this->assertEquals(2, $statusCounts['shipped']);
        $this->assertEquals(1, $statusCounts['completed']);
        $this->assertEquals(1, $statusCounts['cancelled']);
    }
    
    /**
     * 测试订单状态统计总和等于总订单数
     * 验证需求: 1.2
     */
    public function testOrderStatusCountsSumEqualsTotal(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        
        // 创建不同状态的订单
        $this->createTestOrder($user->id, 100.00, Order::STATUS_PENDING);
        $this->createTestOrder($user->id, 200.00, Order::STATUS_PAID);
        $this->createTestOrder($user->id, 150.00, Order::STATUS_SHIPPED);
        $this->createTestOrder($user->id, 180.00, Order::STATUS_COMPLETED);
        $this->createTestOrder($user->id, 220.00, Order::STATUS_CANCELLED);
        
        $totalOrders = $this->service->getTotalOrders();
        $statusCounts = $this->service->getOrderStatusCounts();
        
        $sum = $statusCounts['pending'] + $statusCounts['paid'] + 
               $statusCounts['shipped'] + $statusCounts['completed'] + 
               $statusCounts['cancelled'];
        
        $this->assertEquals($totalOrders, $sum);
    }
    
    /**
     * 测试获取最近10条订单
     * 验证需求: 1.3
     */
    public function testGetRecentOrders(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        
        // 创建15条订单
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestOrder($user->id, 100.00 * $i, Order::STATUS_PENDING);
            // 添加延迟确保创建时间不同
            usleep(10000); // 10毫秒
        }
        
        $recentOrders = $this->service->getRecentOrders();
        
        // 验证返回10条记录
        $this->assertCount(10, $recentOrders);
        
        // 验证按创建时间降序排列（最新的在前）
        for ($i = 0; $i < count($recentOrders) - 1; $i++) {
            $current = strtotime($recentOrders[$i]['created_at']);
            $next = strtotime($recentOrders[$i + 1]['created_at']);
            $this->assertGreaterThanOrEqual($next, $current);
        }
    }
    
    /**
     * 测试订单少于10条时返回所有订单
     * 验证需求: 1.3
     */
    public function testGetRecentOrdersWithLessThan10Orders(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        
        // 创建5条订单
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestOrder($user->id, 100.00 * $i, Order::STATUS_PENDING);
        }
        
        $recentOrders = $this->service->getRecentOrders();
        
        // 验证返回5条记录
        $this->assertCount(5, $recentOrders);
    }
    
    /**
     * 测试获取仪表盘数据
     * 验证需求: 1.1, 1.2, 1.3
     */
    public function testGetDashboardData(): void
    {
        // 创建测试数据
        $user = $this->createTestUser();
        $category = $this->createTestCategory();
        $this->createTestProduct($category->id, '商品1', 100.00);
        $this->createTestProduct($category->id, '商品2', 200.00);
        
        // 创建订单
        $this->createTestOrder($user->id, 100.00, Order::STATUS_PENDING);
        $this->createTestOrder($user->id, 200.00, Order::STATUS_COMPLETED);
        $this->createTestOrder($user->id, 150.00, Order::STATUS_SHIPPED);
        
        $dashboardData = $this->service->getDashboardData();
        
        // 验证数据结构
        $this->assertArrayHasKey('totalOrders', $dashboardData);
        $this->assertArrayHasKey('totalSales', $dashboardData);
        $this->assertArrayHasKey('totalUsers', $dashboardData);
        $this->assertArrayHasKey('totalProducts', $dashboardData);
        $this->assertArrayHasKey('orderStatusCounts', $dashboardData);
        $this->assertArrayHasKey('recentOrders', $dashboardData);
        
        // 验证数据值
        $this->assertEquals(3, $dashboardData['totalOrders']);
        $this->assertEquals(200.00, $dashboardData['totalSales']);
        $this->assertEquals(1, $dashboardData['totalUsers']);
        $this->assertEquals(2, $dashboardData['totalProducts']);
        
        // 验证订单状态统计
        $this->assertEquals(1, $dashboardData['orderStatusCounts']['pending']);
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['paid']);
        $this->assertEquals(1, $dashboardData['orderStatusCounts']['shipped']);
        $this->assertEquals(1, $dashboardData['orderStatusCounts']['completed']);
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['cancelled']);
        
        // 验证最近订单
        $this->assertIsArray($dashboardData['recentOrders']);
        $this->assertCount(3, $dashboardData['recentOrders']);
    }
    
    /**
     * 测试空数据库的仪表盘数据
     * 验证需求: 1.1, 1.2, 1.3
     */
    public function testGetDashboardDataWithEmptyDatabase(): void
    {
        $dashboardData = $this->service->getDashboardData();
        
        $this->assertEquals(0, $dashboardData['totalOrders']);
        $this->assertEquals(0, $dashboardData['totalSales']);
        $this->assertEquals(0, $dashboardData['totalUsers']);
        $this->assertEquals(0, $dashboardData['totalProducts']);
        
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['pending']);
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['paid']);
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['shipped']);
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['completed']);
        $this->assertEquals(0, $dashboardData['orderStatusCounts']['cancelled']);
        
        $this->assertEmpty($dashboardData['recentOrders']);
    }
    
    // 辅助方法
    
    private function createTestUser(string $username = 'testuser', string $email = 'test@example.com'): User
    {
        return User::create([
            'username' => $username,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'email' => $email,
            'phone' => '13800138000',
            'role' => 'user',
            'status' => 1
        ]);
    }
    
    private function createTestCategory(string $name = '测试分类'): Category
    {
        return Category::create([
            'name' => $name,
            'sort_order' => 1,
            'status' => 1
        ]);
    }
    
    private function createTestProduct(int $categoryId, string $name, float $price): Product
    {
        return Product::create([
            'category_id' => $categoryId,
            'name' => $name,
            'description' => '测试商品',
            'price' => $price,
            'stock' => 100,
            'status' => 'on_sale'
        ]);
    }
    
    private function createTestOrder(int $userId, float $totalAmount, string $status): Order
    {
        $orderNo = 'TEST' . date('YmdHis') . rand(1000, 9999);
        
        $order = Order::create([
            'order_no' => $orderNo,
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'status' => $status,
            'address' => [
                'name' => '测试用户',
                'phone' => '13800138000',
                'province' => '广东省',
                'city' => '深圳市',
                'district' => '南山区',
                'detail' => '科技园'
            ]
        ]);
        
        // 如果是已完成订单，设置完成时间
        if ($status === Order::STATUS_COMPLETED) {
            $order->completed_at = date('Y-m-d H:i:s');
            $order->save();
        }
        
        return $order;
    }
}
