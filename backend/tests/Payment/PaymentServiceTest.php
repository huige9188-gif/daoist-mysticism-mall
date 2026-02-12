<?php

namespace Tests\Payment;

use PHPUnit\Framework\TestCase;
use app\service\PaymentService;
use app\model\Order;
use app\model\OrderItem;
use app\model\Product;
use app\model\User;
use app\model\PaymentConfig;
use think\facade\Db;
use PDO;

/**
 * PaymentService测试类
 * 验证需求: 9.1, 9.2, 9.3
 */
class PaymentServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private PaymentService $service;
    
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
        parent::setUp();
        
        // 清空相关表
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        self::$pdo->exec('TRUNCATE TABLE orders');
        self::$pdo->exec('TRUNCATE TABLE order_items');
        self::$pdo->exec('TRUNCATE TABLE users');
        self::$pdo->exec('TRUNCATE TABLE payment_configs');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->service = new PaymentService();
    }
    
    /**
     * 测试创建支付宝支付
     * 验证需求: 9.1
     */
    public function testCreateAlipayPayment(): void
    {
        // 创建支付配置
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC',
            'public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr',
            'notify_url' => 'https://example.com/notify/alipay',
        ];
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 1,
        ]);
        
        // 创建用户和订单
        $user = User::create([
            'username' => 'testuser',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Beijing'],
        ]);
        
        // 创建支付
        $paymentInfo = $this->service->createPayment($order->id, 'alipay');
        
        // 验证返回结果
        $this->assertIsArray($paymentInfo);
        $this->assertArrayHasKey('payment_url', $paymentInfo);
        $this->assertArrayHasKey('order_no', $paymentInfo);
        $this->assertArrayHasKey('gateway', $paymentInfo);
        $this->assertEquals('alipay', $paymentInfo['gateway']);
        $this->assertEquals($order->order_no, $paymentInfo['order_no']);
        
        // 验证订单已更新支付网关
        $order->refresh();
        $this->assertEquals('alipay', $order->payment_gateway);
    }
    
    /**
     * 测试创建微信支付
     * 验证需求: 9.2
     */
    public function testCreateWechatPayment(): void
    {
        // 创建支付配置
        $config = [
            'app_id' => 'wx1234567890abcdef',
            'mch_id' => '1234567890',
            'api_key' => '1234567890abcdef1234567890abcdef',
            'notify_url' => 'https://example.com/notify/wechat',
        ];
        PaymentConfig::create([
            'gateway' => 'wechat',
            'config' => $config,
            'status' => 1,
        ]);
        
        // 创建用户和订单
        $user = User::create([
            'username' => 'testuser2',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test2@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 200.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Shanghai'],
        ]);
        
        // 创建支付
        $paymentInfo = $this->service->createPayment($order->id, 'wechat');
        
        // 验证返回结果
        $this->assertIsArray($paymentInfo);
        $this->assertArrayHasKey('code_url', $paymentInfo);
        $this->assertArrayHasKey('order_no', $paymentInfo);
        $this->assertArrayHasKey('gateway', $paymentInfo);
        $this->assertEquals('wechat', $paymentInfo['gateway']);
        $this->assertEquals($order->order_no, $paymentInfo['order_no']);
        
        // 验证订单已更新支付网关
        $order->refresh();
        $this->assertEquals('wechat', $order->payment_gateway);
    }
    
    /**
     * 测试创建PayPal支付
     * 验证需求: 9.3
     */
    public function testCreatePaypalPayment(): void
    {
        // 创建支付配置
        $config = [
            'client_id' => 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPp',
            'secret' => 'QqRrSsTtUuVvWwXxYyZz0123456789',
            'notify_url' => 'https://example.com/notify/paypal',
        ];
        PaymentConfig::create([
            'gateway' => 'paypal',
            'config' => $config,
            'status' => 1,
        ]);
        
        // 创建用户和订单
        $user = User::create([
            'username' => 'testuser3',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test3@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 300.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Guangzhou'],
        ]);
        
        // 创建支付
        $paymentInfo = $this->service->createPayment($order->id, 'paypal');
        
        // 验证返回结果
        $this->assertIsArray($paymentInfo);
        $this->assertArrayHasKey('payment_id', $paymentInfo);
        $this->assertArrayHasKey('approval_url', $paymentInfo);
        $this->assertArrayHasKey('order_no', $paymentInfo);
        $this->assertArrayHasKey('gateway', $paymentInfo);
        $this->assertEquals('paypal', $paymentInfo['gateway']);
        $this->assertEquals($order->order_no, $paymentInfo['order_no']);
        
        // 验证订单已更新支付网关
        $order->refresh();
        $this->assertEquals('paypal', $order->payment_gateway);
    }
    
    /**
     * 测试订单不存在时创建支付失败
     * 验证需求: 9.1, 9.2, 9.3
     */
    public function testCreatePaymentWithNonExistentOrder(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单不存在');
        
        $this->service->createPayment(99999, 'alipay');
    }
    
    /**
     * 测试订单状态不正确时创建支付失败
     * 验证需求: 9.1, 9.2, 9.3
     */
    public function testCreatePaymentWithInvalidOrderStatus(): void
    {
        // 创建已支付的订单
        $user = User::create([
            'username' => 'testuser4',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test4@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PAID,
            'address' => ['city' => 'Beijing'],
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单状态不正确');
        
        $this->service->createPayment($order->id, 'alipay');
    }
    
    /**
     * 测试支付配置不存在时创建支付失败
     * 验证需求: 9.1, 9.2, 9.3
     */
    public function testCreatePaymentWithNonExistentConfig(): void
    {
        // 创建订单
        $user = User::create([
            'username' => 'testuser5',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test5@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Beijing'],
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('支付配置不存在');
        
        $this->service->createPayment($order->id, 'alipay');
    }
    
    /**
     * 测试支付方式未启用时创建支付失败
     * 验证需求: 9.6
     */
    public function testCreatePaymentWithDisabledGateway(): void
    {
        // 创建禁用的支付配置
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'test_private_key',
            'public_key' => 'test_public_key',
            'notify_url' => 'https://example.com/notify/alipay',
        ];
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 0, // 禁用
        ]);
        
        // 创建订单
        $user = User::create([
            'username' => 'testuser6',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test6@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Beijing'],
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('支付方式未启用');
        
        $this->service->createPayment($order->id, 'alipay');
    }
    
    /**
     * 测试处理支付宝回调
     * 验证需求: 9.1
     */
    public function testHandleAlipayCallback(): void
    {
        // 创建支付配置
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC',
            'public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr',
            'notify_url' => 'https://example.com/notify/alipay',
        ];
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 1,
        ]);
        
        // 创建订单
        $user = User::create([
            'username' => 'testuser7',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test7@example.com',
            'role' => 'user',
        ]);
        
        $orderNo = Order::generateOrderNo();
        $order = Order::create([
            'order_no' => $orderNo,
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Beijing'],
            'payment_gateway' => 'alipay',
        ]);
        
        // 模拟回调数据
        $callbackData = [
            'out_trade_no' => $orderNo,
            'trade_no' => '2024010122001234567890',
            'total_amount' => '100.00',
            'trade_status' => 'TRADE_SUCCESS',
            'gmt_payment' => '2024-01-01 12:00:00',
            'sign' => 'test_sign',
            'sign_type' => 'RSA2',
        ];
        
        // 处理回调
        $updatedOrder = $this->service->handleCallback('alipay', $callbackData);
        
        // 验证订单状态已更新
        $this->assertEquals(Order::STATUS_PAID, $updatedOrder->status);
        $this->assertNotNull($updatedOrder->paid_at);
    }
    
    /**
     * 测试获取可用支付方式
     * 验证需求: 9.5
     */
    public function testGetAvailableGateways(): void
    {
        // 创建多个支付配置
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => ['test' => 'value'],
            'status' => 1,
        ]);
        
        PaymentConfig::create([
            'gateway' => 'wechat',
            'config' => ['test' => 'value'],
            'status' => 1,
        ]);
        
        PaymentConfig::create([
            'gateway' => 'paypal',
            'config' => ['test' => 'value'],
            'status' => 0, // 禁用
        ]);
        
        // 获取可用支付方式
        $gateways = $this->service->getAvailableGateways();
        
        // 验证结果
        $this->assertIsArray($gateways);
        $this->assertCount(2, $gateways); // 只有2个启用的
        
        $gatewayNames = array_column($gateways, 'gateway');
        $this->assertContains('alipay', $gatewayNames);
        $this->assertContains('wechat', $gatewayNames);
        $this->assertNotContains('paypal', $gatewayNames);
    }
    
    /**
     * 测试退款
     * 验证需求: 9.1, 9.2, 9.3
     */
    public function testRefund(): void
    {
        // 创建支付配置
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'test_private_key',
            'public_key' => 'test_public_key',
            'notify_url' => 'https://example.com/notify/alipay',
        ];
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 1,
        ]);
        
        // 创建已支付的订单
        $user = User::create([
            'username' => 'testuser8',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test8@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PAID,
            'address' => ['city' => 'Beijing'],
            'payment_gateway' => 'alipay',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 执行退款
        $refundResult = $this->service->refund($order);
        
        // 验证退款结果
        $this->assertIsArray($refundResult);
        $this->assertArrayHasKey('success', $refundResult);
        $this->assertTrue($refundResult['success']);
        $this->assertArrayHasKey('refund_amount', $refundResult);
        $this->assertEquals($order->total_amount, $refundResult['refund_amount']);
    }
    
    /**
     * 测试未支付订单退款失败
     * 验证需求: 9.1, 9.2, 9.3
     */
    public function testRefundUnpaidOrder(): void
    {
        // 创建未支付的订单
        $user = User::create([
            'username' => 'testuser9',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test9@example.com',
            'role' => 'user',
        ]);
        
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => Order::STATUS_PENDING,
            'address' => ['city' => 'Beijing'],
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单未支付，无需退款');
        
        $this->service->refund($order);
    }
}
