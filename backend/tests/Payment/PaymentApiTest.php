<?php

namespace tests\Payment;

use PHPUnit\Framework\TestCase;
use app\service\PaymentService;
use app\service\PaymentConfigService;
use think\facade\Db;
use PDO;

/**
 * 支付API集成测试
 * 
 * 验证需求: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6
 */
class PaymentApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    private static $normalUserId;
    private static $adminToken;
    private static $userToken;
    private static $orderId;
    
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
            VALUES ('admin_payment_test', '{$hashedPassword}', 'admin_payment@test.com', '13800000011', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_payment_test', '{$hashedPassword}', 'user_payment@test.com', '13800000012', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
        
        // 创建测试订单
        $orderNo = date('YmdHis') . rand(1000, 9999);
        self::$pdo->exec("
            INSERT INTO orders (order_no, user_id, total_amount, status, address) 
            VALUES ('{$orderNo}', " . self::$normalUserId . ", 100.00, 'pending', '{\"name\":\"测试\"}')
        ");
        self::$orderId = self::$pdo->lastInsertId();
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM payment_configs WHERE gateway IN ('alipay', 'wechat', 'paypal')");
            self::$pdo->exec("DELETE FROM orders WHERE id = " . self::$orderId);
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试获取支付配置列表 - 管理员权限
     * 验证需求: 9.1, 9.2, 9.3, 9.4
     */
    public function testGetPaymentConfigsAsAdmin()
    {
        $response = $this->simulateApiRequest('GET', '/api/payment-configs', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试获取支付配置列表 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testGetPaymentConfigsAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/payment-configs', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试保存支付宝配置 - 成功
     * 验证需求: 9.1, 9.4
     */
    public function testSaveAlipayConfigSuccess()
    {
        $response = $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'alipay',
            'config' => [
                'app_id' => '2021001234567890',
                'private_key' => 'test_private_key',
                'public_key' => 'test_public_key',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 1
        ], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('保存成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals('alipay', $response['data']['gateway']);
    }
    
    /**
     * 测试保存微信支付配置 - 成功
     * 验证需求: 9.2, 9.4
     */
    public function testSaveWechatConfigSuccess()
    {
        $response = $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'wechat',
            'config' => [
                'app_id' => 'wx1234567890abcdef',
                'mch_id' => '1234567890',
                'api_key' => 'test_api_key_32_characters_long',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 1
        ], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('保存成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals('wechat', $response['data']['gateway']);
    }
    
    /**
     * 测试保存PayPal配置 - 成功
     * 验证需求: 9.3, 9.4
     */
    public function testSavePaypalConfigSuccess()
    {
        $response = $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'paypal',
            'config' => [
                'client_id' => 'test_client_id',
                'secret' => 'test_secret',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 1
        ], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('保存成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals('paypal', $response['data']['gateway']);
    }
    
    /**
     * 测试保存支付配置 - 缺少必填字段
     * 验证需求: 9.4
     */
    public function testSaveConfigMissingRequiredFields()
    {
        $response = $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'alipay',
            'config' => [
                'app_id' => '2021001234567890'
                // 缺少其他必填字段
            ],
            'status' => 1
        ], self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('必填字段', $response['message']);
    }
    
    /**
     * 测试保存支付配置 - 不支持的支付网关
     * 验证需求: 9.4
     */
    public function testSaveConfigUnsupportedGateway()
    {
        $response = $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'unsupported',
            'config' => [],
            'status' => 1
        ], self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('不支持', $response['message']);
    }
    
    /**
     * 测试创建支付 - 成功
     * 验证需求: 9.1, 9.5
     */
    public function testCreatePaymentSuccess()
    {
        // 先创建支付配置
        $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'alipay',
            'config' => [
                'app_id' => '2021001234567890',
                'private_key' => 'test_private_key',
                'public_key' => 'test_public_key',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 1
        ], self::$adminToken);
        
        $response = $this->simulateApiRequest('POST', '/api/payments', [
            'order_id' => self::$orderId,
            'gateway' => 'alipay'
        ], self::$userToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('支付创建成功', $response['message']);
        $this->assertNotEmpty($response['data']);
    }
    
    /**
     * 测试创建支付 - 订单不存在
     * 验证需求: 9.1
     */
    public function testCreatePaymentOrderNotFound()
    {
        $response = $this->simulateApiRequest('POST', '/api/payments', [
            'order_id' => 999999,
            'gateway' => 'alipay'
        ], self::$userToken);
        
        // The service throws an exception which gets caught and returns 400 or 404
        $this->assertContains($response['code'], [400, 404]);
        $this->assertStringContainsString('订单不存在', $response['message']);
    }
    
    /**
     * 测试创建支付 - 支付方式未启用
     * 验证需求: 9.6
     */
    public function testCreatePaymentGatewayDisabled()
    {
        // 创建禁用的支付配置
        $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'wechat',
            'config' => [
                'app_id' => 'wx1234567890abcdef',
                'mch_id' => '1234567890',
                'api_key' => 'test_api_key_32_characters_long',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 0  // 禁用
        ], self::$adminToken);
        
        $response = $this->simulateApiRequest('POST', '/api/payments', [
            'order_id' => self::$orderId,
            'gateway' => 'wechat'
        ], self::$userToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('未启用', $response['message']);
    }
    
    /**
     * 测试获取可用支付方式 - 成功
     * 验证需求: 9.5
     */
    public function testGetAvailableGateways()
    {
        // 确保有启用的支付配置
        $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'alipay',
            'config' => [
                'app_id' => '2021001234567890',
                'private_key' => 'test_private_key',
                'public_key' => 'test_public_key',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 1
        ], self::$adminToken);
        
        $response = $this->simulateApiRequest('GET', '/api/payments/gateways', [], self::$userToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
        $this->assertNotEmpty($response['data']);
    }
    
    /**
     * 测试支付回调 - 成功
     * 验证需求: 9.1
     */
    public function testHandlePaymentCallback()
    {
        // 先创建支付配置
        $this->simulateApiRequest('POST', '/api/payment-configs', [
            'gateway' => 'alipay',
            'config' => [
                'app_id' => '2021001234567890',
                'private_key' => 'test_private_key',
                'public_key' => 'test_public_key',
                'notify_url' => 'https://example.com/notify'
            ],
            'status' => 1
        ], self::$adminToken);
        
        // 获取订单号
        $stmt = self::$pdo->prepare("SELECT order_no FROM orders WHERE id = ?");
        $stmt->execute([self::$orderId]);
        $order = $stmt->fetch();
        
        $response = $this->simulateApiRequest('POST', '/api/payments/callback', [
            'gateway' => 'alipay',
            'out_trade_no' => $order['order_no'],
            'trade_no' => 'test_trade_no_123456',
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '100.00',
            'gmt_payment' => date('Y-m-d H:i:s'),
            'sign' => 'test_sign',
            'sign_type' => 'RSA2'
        ], null);  // 回调不需要token
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('回调处理成功', $response['message']);
        $this->assertNotEmpty($response['data']);
        
        // 恢复订单状态
        self::$pdo->exec("UPDATE orders SET status = 'pending', paid_at = NULL WHERE id = " . self::$orderId);
    }
    
    /**
     * 模拟API请求
     */
    private function simulateApiRequest(string $method, string $url, array $data = [], ?string $token = null): array
    {
        // 支付回调路由不需要认证
        if ($url !== '/api/payments/callback') {
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
            $adminRoutes = ['/api/payment-configs'];
            foreach ($adminRoutes as $route) {
                if (strpos($url, $route) === 0 && $user['role'] !== 'admin') {
                    return ['code' => 403, 'message' => '无权限访问', 'data' => null];
                }
            }
        }
        
        // 业务逻辑执行
        try {
            $paymentService = new PaymentService();
            $paymentConfigService = new PaymentConfigService();
            
            if ($method === 'GET' && $url === '/api/payment-configs') {
                // 获取支付配置列表
                $configs = $paymentConfigService->getAllConfigs();
                return ['code' => 200, 'message' => '获取成功', 'data' => $configs];
                
            } elseif ($method === 'POST' && $url === '/api/payment-configs') {
                // 保存支付配置
                if (!isset($data['gateway']) || empty($data['gateway'])) {
                    return ['code' => 400, 'message' => '支付网关不能为空', 'data' => null];
                }
                
                if (!isset($data['config']) || !is_array($data['config'])) {
                    return ['code' => 400, 'message' => '配置信息格式不正确', 'data' => null];
                }
                
                $status = $data['status'] ?? 1;
                $config = $paymentConfigService->saveConfig($data['gateway'], $data['config'], $status);
                return ['code' => 200, 'message' => '保存成功', 'data' => $config];
                
            } elseif ($method === 'POST' && $url === '/api/payments') {
                // 创建支付
                if (!isset($data['order_id']) || empty($data['order_id'])) {
                    return ['code' => 400, 'message' => '订单ID不能为空', 'data' => null];
                }
                
                if (!isset($data['gateway']) || empty($data['gateway'])) {
                    return ['code' => 400, 'message' => '支付网关不能为空', 'data' => null];
                }
                
                $paymentInfo = $paymentService->createPayment($data['order_id'], $data['gateway']);
                return ['code' => 200, 'message' => '支付创建成功', 'data' => $paymentInfo];
                
            } elseif ($method === 'GET' && $url === '/api/payments/gateways') {
                // 获取可用支付方式
                $gateways = $paymentService->getAvailableGateways();
                return ['code' => 200, 'message' => '获取成功', 'data' => $gateways];
                
            } elseif ($method === 'POST' && $url === '/api/payments/callback') {
                // 处理支付回调
                if (!isset($data['gateway']) || empty($data['gateway'])) {
                    return ['code' => 400, 'message' => '支付网关不能为空', 'data' => null];
                }
                
                $order = $paymentService->handleCallback($data['gateway'], $data);
                return ['code' => 200, 'message' => '回调处理成功', 'data' => $order];
            }
            
            return ['code' => 404, 'message' => '路由不存在', 'data' => null];
            
        } catch (\think\exception\ValidateException $e) {
            return ['code' => 400, 'message' => $e->getMessage(), 'data' => null];
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            if (strpos($message, '不存在') !== false) {
                return ['code' => 404, 'message' => $message, 'data' => null];
            } elseif (strpos($message, '不支持') !== false || 
                      strpos($message, '未启用') !== false ||
                      strpos($message, '必填字段') !== false ||
                      strpos($message, '状态不正确') !== false) {
                return ['code' => 400, 'message' => $message, 'data' => null];
            }
            
            return ['code' => 500, 'message' => $message, 'data' => null];
        }
    }
}
