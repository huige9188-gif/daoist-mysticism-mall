<?php

namespace tests\Dashboard;

use PHPUnit\Framework\TestCase;
use app\model\User;
use app\model\Product;
use app\model\Order;
use app\model\Category;
use think\facade\Db;
use PDO;

/**
 * 仪表盘API测试
 * 
 * 验证需求: 1.1, 1.2, 1.3
 */
class DashboardApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    private static $adminToken;
    
    public static function setUpBeforeClass(): void
    {
        // 连接测试数据库
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
        
        // 创建管理员用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('admin_dashboard_test', '{$hashedPassword}', 'admin_dashboard@test.com', '13800000099', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            self::$pdo->exec("DELETE FROM users WHERE username LIKE '%dashboard_test%'");
            self::$pdo->exec("DELETE FROM products WHERE name LIKE 'Test Product%'");
            self::$pdo->exec("DELETE FROM orders WHERE order_no LIKE 'TEST%'");
            self::$pdo->exec("DELETE FROM categories WHERE name LIKE 'Test Category%'");
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    }
    
    protected function setUp(): void
    {
        // 每个测试前清理订单、商品、分类数据
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        self::$pdo->exec("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE order_no LIKE 'TEST%')");
        self::$pdo->exec("DELETE FROM orders WHERE order_no LIKE 'TEST%'");
        self::$pdo->exec("DELETE FROM products WHERE name LIKE 'Test Product%'");
        self::$pdo->exec("DELETE FROM categories WHERE name LIKE 'Test Category%'");
        self::$pdo->exec("DELETE FROM users WHERE username LIKE 'test_user_%'");
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * 测试获取仪表盘统计数据
     * 验证需求: 1.1, 1.2, 1.3
     */
    public function testGetDashboardStats()
    {
        // 创建测试数据
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('Test Category Dashboard', 1, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, 'Test Product Dashboard', 100.00, 10, 'on_sale')
        ");
        
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, role, status) 
            VALUES ('test_user_dashboard', '{$hashedPassword}', 'test_dashboard@test.com', 'user', 1)
        ");
        $userId = self::$pdo->lastInsertId();
        
        // 创建不同状态的订单
        $timestamp = time();
        self::$pdo->exec("
            INSERT INTO orders (order_no, user_id, total_amount, status) 
            VALUES 
                ('TEST{$timestamp}001', {$userId}, 100.00, 'pending'),
                ('TEST{$timestamp}002', {$userId}, 200.00, 'paid'),
                ('TEST{$timestamp}003', {$userId}, 300.00, 'completed')
        ");
        
        // 调用统计服务
        $statisticsService = new \app\service\StatisticsService();
        $data = $statisticsService->getDashboardData();
        
        // 验证数据结构
        $this->assertArrayHasKey('totalOrders', $data);
        $this->assertArrayHasKey('totalSales', $data);
        $this->assertArrayHasKey('totalUsers', $data);
        $this->assertArrayHasKey('totalProducts', $data);
        $this->assertArrayHasKey('orderStatusCounts', $data);
        $this->assertArrayHasKey('recentOrders', $data);
        
        // 验证统计数据准确性（需求 1.1）
        $this->assertGreaterThanOrEqual(3, $data['totalOrders']);
        $this->assertGreaterThanOrEqual(2, $data['totalUsers']);
        $this->assertGreaterThanOrEqual(1, $data['totalProducts']);
        
        // 验证订单状态统计（需求 1.2）
        $this->assertArrayHasKey('pending', $data['orderStatusCounts']);
        $this->assertArrayHasKey('paid', $data['orderStatusCounts']);
        $this->assertArrayHasKey('shipped', $data['orderStatusCounts']);
        $this->assertArrayHasKey('completed', $data['orderStatusCounts']);
        $this->assertArrayHasKey('cancelled', $data['orderStatusCounts']);
        
        $this->assertGreaterThanOrEqual(1, $data['orderStatusCounts']['pending']);
        $this->assertGreaterThanOrEqual(1, $data['orderStatusCounts']['paid']);
        $this->assertGreaterThanOrEqual(1, $data['orderStatusCounts']['completed']);
        
        // 验证最近订单列表（需求 1.3）
        $this->assertIsArray($data['recentOrders']);
        $this->assertLessThanOrEqual(10, count($data['recentOrders']));
        
        // 验证订单按时间降序排列
        if (count($data['recentOrders']) > 1) {
            $firstOrder = $data['recentOrders'][0];
            $secondOrder = $data['recentOrders'][1];
            $this->assertGreaterThanOrEqual(
                strtotime($secondOrder['created_at']),
                strtotime($firstOrder['created_at']),
                '订单应按创建时间降序排列'
            );
        }
    }
    
    /**
     * 测试仪表盘响应时间
     * 验证需求: 1.4
     */
    public function testDashboardResponseTime()
    {
        // 创建一些测试数据
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('Test Category Speed', 1, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, 'Test Product Speed', 100.00, 10, 'on_sale')
        ");
        
        $startTime = microtime(true);
        
        $statisticsService = new \app\service\StatisticsService();
        $data = $statisticsService->getDashboardData();
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        // 验证响应时间在2秒内（需求 1.4）
        $this->assertLessThan(2.0, $responseTime, '仪表盘响应时间应在2秒内');
        $this->assertNotEmpty($data);
    }
    
    /**
     * 测试空数据库的仪表盘统计
     */
    public function testDashboardStatsWithEmptyDatabase()
    {
        $statisticsService = new \app\service\StatisticsService();
        $data = $statisticsService->getDashboardData();
        
        // 验证数据结构存在
        $this->assertArrayHasKey('totalOrders', $data);
        $this->assertArrayHasKey('totalSales', $data);
        $this->assertArrayHasKey('totalUsers', $data);
        $this->assertArrayHasKey('totalProducts', $data);
        $this->assertArrayHasKey('orderStatusCounts', $data);
        $this->assertArrayHasKey('recentOrders', $data);
        
        // 验证空数据库返回0或空数组
        $this->assertIsNumeric($data['totalOrders']);
        $this->assertIsNumeric($data['totalSales']);
        $this->assertIsArray($data['recentOrders']);
    }
}
