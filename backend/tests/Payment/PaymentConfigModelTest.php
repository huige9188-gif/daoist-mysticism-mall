<?php

namespace Tests\Payment;

use PHPUnit\Framework\TestCase;
use app\model\PaymentConfig;
use think\facade\Db;
use PDO;

/**
 * 支付配置模型测试
 */
class PaymentConfigModelTest extends TestCase
{
    private static ?PDO $pdo = null;
    
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
        // 清空支付配置表
        self::$pdo->exec("TRUNCATE TABLE payment_configs");
    }
    
    /**
     * 测试创建支付配置
     */
    public function testCreatePaymentConfig(): void
    {
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $paymentConfig = PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 1
        ]);
        
        $this->assertInstanceOf(PaymentConfig::class, $paymentConfig);
        $this->assertEquals('alipay', $paymentConfig->gateway);
        $this->assertEquals($config, $paymentConfig->config);
        $this->assertEquals(1, $paymentConfig->status);
    }
    
    /**
     * 测试JSON类型转换
     */
    public function testJsonTypeConversion(): void
    {
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $paymentConfig = PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 1
        ]);
        
        // 重新查询
        $found = PaymentConfig::find($paymentConfig->id);
        
        // 验证config字段被正确转换为数组
        $this->assertIsArray($found->config);
        $this->assertEquals($config, $found->config);
    }
    
    /**
     * 测试自动时间戳
     */
    public function testAutoTimestamp(): void
    {
        $paymentConfig = PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => ['test' => 'value'],
            'status' => 1
        ]);
        
        $this->assertNotNull($paymentConfig->created_at);
        $this->assertNotNull($paymentConfig->updated_at);
    }
    
    /**
     * 测试获取支持的支付网关
     */
    public function testGetSupportedGateways(): void
    {
        $gateways = PaymentConfig::getSupportedGateways();
        
        $this->assertIsArray($gateways);
        $this->assertContains('alipay', $gateways);
        $this->assertContains('wechat', $gateways);
        $this->assertContains('paypal', $gateways);
    }
    
    /**
     * 测试isActive方法
     */
    public function testIsActive(): void
    {
        $activeConfig = PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => ['test' => 'value'],
            'status' => 1
        ]);
        
        $inactiveConfig = PaymentConfig::create([
            'gateway' => 'wechat',
            'config' => ['test' => 'value'],
            'status' => 0
        ]);
        
        $this->assertTrue($activeConfig->isActive());
        $this->assertFalse($inactiveConfig->isActive());
    }
    
    /**
     * 测试getConfigValue方法
     */
    public function testGetConfigValue(): void
    {
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $paymentConfig = PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => $config,
            'status' => 1
        ]);
        
        $this->assertEquals('2021001234567890', $paymentConfig->getConfigValue('app_id'));
        $this->assertEquals('private_key', $paymentConfig->getConfigValue('private_key'));
        $this->assertNull($paymentConfig->getConfigValue('non_existent_key'));
        $this->assertEquals('default_value', $paymentConfig->getConfigValue('non_existent_key', 'default_value'));
    }
    
    /**
     * 测试网关唯一性约束
     */
    public function testGatewayUniqueness(): void
    {
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => ['test' => 'value1'],
            'status' => 1
        ]);
        
        // 尝试创建相同网关的配置应该失败
        $this->expectException(\think\db\exception\PDOException::class);
        
        PaymentConfig::create([
            'gateway' => 'alipay',
            'config' => ['test' => 'value2'],
            'status' => 1
        ]);
    }
}
