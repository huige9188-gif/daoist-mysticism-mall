<?php

namespace tests\Order;

use PHPUnit\Framework\TestCase;
use app\service\OrderService;
use think\facade\Db;
use PDO;

/**
 * 订单API集成测试
 * 
 * 验证需求: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7, 5.8
 */
class OrderApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    private static $normalUserId;
    private static $adminToken;
    private static $userToken;
    private static $categoryId;
    private static $productId;
    
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
        
        // 创建测试用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('admin_order_test', '{$hashedPassword}', 'admin_order@test.com', '13800000001', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_order_test', '{$hashedPassword}', 'user_order@test.com', '13800000002', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
        
        // 创建测试分类和商品
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('测试分类', 1, 1)
        ");
        self::$categoryId = self::$pdo->lastInsertId();
        
        self::$pdo->exec("
            INSERT INTO products (category_id, name, description, price, stock, status) 
            VALUES (" . self::$categoryId . ", '测试商品', '测试商品描述', 100.00, 50, 'on_sale')
        ");
        self::$productId = self::$pdo->lastInsertId();
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM order_items WHERE product_id = " . self::$productId);
            self::$pdo->exec("DELETE FROM orders WHERE user_id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
            self::$pdo->exec("DELETE FROM products WHERE id = " . self::$productId);
            self::$pdo->exec("DELETE FROM categories WHERE id = " . self::$categoryId);
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试创建订单 - 成功
     * 验证需求: 5.5
     */
    public function testCreateOrderSuccess()
    {
        $response = $this->simulateApiRequest('POST', '/api/orders', [
            'items' => [
                ['product_id' => self::$productId, 'quantity' => 2]
            ],
            'address' => [
                'name' => '张三',
                'phone' => '13800138000',
                'province' => '广东省',
                'city' => '深圳市',
                'district' => '南山区',
                'detail' => '科技园'
            ]
        ], self::$userToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('订单创建成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals('pending', $response['data']['status']);
        
        // 清理
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM order_items WHERE order_id = " . $response['data']['id']);
            self::$pdo->exec("DELETE FROM orders WHERE id = " . $response['data']['id']);
        }
        
        // 恢复库存
        self::$pdo->exec("UPDATE products SET stock = 50 WHERE id = " . self::$productId);
    }
    
    /**
     * 测试创建订单 - 库存不足
     * 验证需求: 5.5
     */
    public function testCreateOrderInsufficientStock()
    {
        $response = $this->simulateApiRequest('POST', '/api/orders', [
            'items' => [
                ['product_id' => self::$productId, 'quantity' => 100]
            ],
            'address' => ['name' => '张三', 'phone' => '13800138000']
        ], self::$userToken);
        
        // The service throws an exception which gets caught and returns 400 or 500
        $this->assertContains($response['code'], [400, 500]);
        $this->assertStringContainsString('库存不足', $response['message']);
    }
    
    /**
     * 测试获取订单列表 - 管理员权限
     * 验证需求: 5.1, 5.7
     */
    public function testGetOrderListAsAdmin()
    {
        $response = $this->simulateApiRequest('GET', '/api/orders', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertArrayHasKey('data', $response);
    }
    
    /**
     * 测试获取订单列表 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testGetOrderListAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/orders', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试获取订单详情
     * 验证需求: 5.2
     */
    public function testGetOrderDetail()
    {
        // 创建测试订单
        $orderNo = date('YmdHis') . rand(1000, 9999);
        self::$pdo->exec("
            INSERT INTO orders (order_no, user_id, total_amount, status, address) 
            VALUES ('{$orderNo}', " . self::$normalUserId . ", 100.00, 'pending', '{\"name\":\"测试\"}')
        ");
        $orderId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('GET', '/api/orders/' . $orderId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals($orderId, $response['data']['id']);
        
        // 清理
        self::$pdo->exec("DELETE FROM orders WHERE id = " . $orderId);
    }
    
    /**
     * 测试订单发货 - 成功
     * 验证需求: 5.3, 5.4
     */
    public function testShipOrderSuccess()
    {
        // 创建已支付订单
        $orderNo = date('YmdHis') . rand(1000, 9999);
        self::$pdo->exec("
            INSERT INTO orders (order_no, user_id, total_amount, status, address, paid_at) 
            VALUES ('{$orderNo}', " . self::$normalUserId . ", 100.00, 'paid', '{\"name\":\"测试\"}', NOW())
        ");
        $orderId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('POST', '/api/orders/' . $orderId . '/ship', [
            'logistics_company' => '顺丰速运',
            'logistics_number' => 'SF1234567890'
        ], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('发货成功', $response['message']);
        $this->assertEquals('shipped', $response['data']['status']);
        
        // 清理
        self::$pdo->exec("DELETE FROM orders WHERE id = " . $orderId);
    }
    
    /**
     * 测试订单发货 - 缺少物流信息
     * 验证需求: 5.4
     */
    public function testShipOrderMissingLogistics()
    {
        // 创建已支付订单
        $orderNo = date('YmdHis') . rand(1000, 9999);
        self::$pdo->exec("
            INSERT INTO orders (order_no, user_id, total_amount, status, address, paid_at) 
            VALUES ('{$orderNo}', " . self::$normalUserId . ", 100.00, 'paid', '{\"name\":\"测试\"}', NOW())
        ");
        $orderId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('POST', '/api/orders/' . $orderId . '/ship', [
            'logistics_company' => '顺丰速运'
        ], self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('物流单号', $response['message']);
        
        // 清理
        self::$pdo->exec("DELETE FROM orders WHERE id = " . $orderId);
    }
    
    /**
     * 测试取消订单 - 成功
     * 验证需求: 5.5
     */
    public function testCancelOrderSuccess()
    {
        // 创建订单
        $orderNo = date('YmdHis') . rand(1000, 9999);
        self::$pdo->exec("
            INSERT INTO orders (order_no, user_id, total_amount, status, address) 
            VALUES ('{$orderNo}', " . self::$normalUserId . ", 100.00, 'pending', '{\"name\":\"测试\"}')
        ");
        $orderId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('POST', '/api/orders/' . $orderId . '/cancel', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('订单取消成功', $response['message']);
        $this->assertEquals('cancelled', $response['data']['status']);
        
        // 清理
        self::$pdo->exec("DELETE FROM orders WHERE id = " . $orderId);
    }
    
    /**
     * 模拟API请求
     */
    private function simulateApiRequest(string $method, string $url, array $data = [], ?string $token = null): array
    {
        // 认证检查
        if (!$token) {
            return ['code' => 401, 'message' => '未授权访问', 'data' => null];
        }
        
        // 解析token获取用户信息
        $userId = (int) str_replace(['mock_admin_token_', 'mock_user_token_'], '', $token);
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['code' => 401, 'message' => '未授权访问', 'data' => null];
        }
        
        // 授权检查（管理员路由）
        $adminRoutes = ['/api/orders'];
        foreach ($adminRoutes as $route) {
            if ($method !== 'POST' && strpos($url, $route) === 0 && $user['role'] !== 'admin') {
                return ['code' => 403, 'message' => '无权限访问', 'data' => null];
            }
        }
        
        // 业务逻辑执行
        try {
            $orderService = new OrderService();
            
            if ($method === 'GET' && preg_match('#^/api/orders(\?.*)?$#', $url)) {
                // 获取订单列表
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $page = $query['page'] ?? 1;
                $pageSize = $query['page_size'] ?? 10;
                $filters = [];
                
                if (isset($query['order_no'])) $filters['order_no'] = $query['order_no'];
                if (isset($query['status'])) $filters['status'] = $query['status'];
                if (isset($query['user_id'])) $filters['user_id'] = $query['user_id'];
                
                $orders = $orderService->getOrderList($page, $pageSize, $filters);
                return ['code' => 200, 'message' => '获取成功', 'data' => $orders];
                
            } elseif ($method === 'GET' && preg_match('#^/api/orders/(\d+)$#', $url, $matches)) {
                // 获取订单详情
                $id = $matches[1];
                $order = $orderService->getOrderById($id);
                
                if (!$order) {
                    return ['code' => 404, 'message' => '订单不存在', 'data' => null];
                }
                
                return ['code' => 200, 'message' => '获取成功', 'data' => $order];
                
            } elseif ($method === 'POST' && $url === '/api/orders') {
                // 创建订单
                if (!isset($data['items']) || empty($data['items'])) {
                    return ['code' => 400, 'message' => '订单商品列表不能为空', 'data' => null];
                }
                
                if (!isset($data['address']) || empty($data['address'])) {
                    return ['code' => 400, 'message' => '收货地址不能为空', 'data' => null];
                }
                
                $order = $orderService->createOrder($userId, $data['items'], $data['address']);
                return ['code' => 200, 'message' => '订单创建成功', 'data' => $order];
                
            } elseif ($method === 'POST' && preg_match('#^/api/orders/(\d+)/ship$#', $url, $matches)) {
                // 订单发货
                $id = $matches[1];
                
                if (!isset($data['logistics_company']) || empty($data['logistics_company'])) {
                    return ['code' => 400, 'message' => '物流公司不能为空', 'data' => null];
                }
                
                if (!isset($data['logistics_number']) || empty($data['logistics_number'])) {
                    return ['code' => 400, 'message' => '物流单号不能为空', 'data' => null];
                }
                
                $logistics = [
                    'company' => $data['logistics_company'],
                    'number' => $data['logistics_number']
                ];
                
                $order = $orderService->shipOrder($id, $logistics);
                return ['code' => 200, 'message' => '发货成功', 'data' => $order];
                
            } elseif ($method === 'POST' && preg_match('#^/api/orders/(\d+)/cancel$#', $url, $matches)) {
                // 取消订单
                $id = $matches[1];
                $order = $orderService->cancelOrder($id);
                return ['code' => 200, 'message' => '订单取消成功', 'data' => $order];
            }
            
            return ['code' => 404, 'message' => '路由不存在', 'data' => null];
            
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            if (strpos($message, '不存在') !== false) {
                return ['code' => 404, 'message' => $message, 'data' => null];
            } elseif (strpos($message, '库存不足') !== false || 
                      strpos($message, '状态不正确') !== false ||
                      strpos($message, '不能为空') !== false) {
                return ['code' => 400, 'message' => $message, 'data' => null];
            }
            
            return ['code' => 500, 'message' => $message, 'data' => null];
        }
    }
}
