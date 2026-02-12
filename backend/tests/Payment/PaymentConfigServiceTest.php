<?php

namespace Tests\Payment;

use PHPUnit\Framework\TestCase;
use app\model\PaymentConfig;
use app\service\PaymentConfigService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 支付配置服务测试
 * 
 * 验证需求: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6
 */
class PaymentConfigServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private PaymentConfigService $service;
    
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
        $this->service = new PaymentConfigService();
        
        // 清空支付配置表
        self::$pdo->exec("TRUNCATE TABLE payment_configs");
    }
    
    /**
     * 测试创建支付宝配置成功
     * 验证需求: 9.1, 9.4
     */
    public function testSaveAlipayConfigSuccess(): void
    {
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...',
            'public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw...',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $paymentConfig = $this->service->saveConfig('alipay', $config);
        
        $this->assertInstanceOf(PaymentConfig::class, $paymentConfig);
        $this->assertEquals('alipay', $paymentConfig->gateway);
        $this->assertEquals($config, $paymentConfig->config);
        $this->assertEquals(1, $paymentConfig->status);
    }
    
    /**
     * 测试创建支付宝配置时缺少必填字段
     * 验证需求: 9.1, 9.4
     */
    public function testSaveAlipayConfigWithMissingFields(): void
    {
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...',
            // 缺少 public_key 和 notify_url
        ];
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('缺少必填字段');
        
        $this->service->saveConfig('alipay', $config);
    }
    
    /**
     * 测试创建微信支付配置成功
     * 验证需求: 9.2, 9.4
     */
    public function testSaveWechatConfigSuccess(): void
    {
        $config = [
            'app_id' => 'wx1234567890abcdef',
            'mch_id' => '1234567890',
            'api_key' => '32characterslongapikey123456789',
            'notify_url' => 'https://example.com/notify/wechat'
        ];
        
        $paymentConfig = $this->service->saveConfig('wechat', $config);
        
        $this->assertInstanceOf(PaymentConfig::class, $paymentConfig);
        $this->assertEquals('wechat', $paymentConfig->gateway);
        $this->assertEquals($config, $paymentConfig->config);
    }
    
    /**
     * 测试创建微信支付配置时缺少必填字段
     * 验证需求: 9.2, 9.4
     */
    public function testSaveWechatConfigWithMissingFields(): void
    {
        $config = [
            'app_id' => 'wx1234567890abcdef',
            'mch_id' => '1234567890',
            // 缺少 api_key 和 notify_url
        ];
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('缺少必填字段');
        
        $this->service->saveConfig('wechat', $config);
    }
    
    /**
     * 测试创建PayPal配置成功
     * 验证需求: 9.3, 9.4
     */
    public function testSavePaypalConfigSuccess(): void
    {
        $config = [
            'client_id' => 'AeB1234567890abcdefghijklmnopqrstuvwxyz',
            'secret' => 'EFG1234567890abcdefghijklmnopqrstuvwxyz',
            'notify_url' => 'https://example.com/notify/paypal'
        ];
        
        $paymentConfig = $this->service->saveConfig('paypal', $config);
        
        $this->assertInstanceOf(PaymentConfig::class, $paymentConfig);
        $this->assertEquals('paypal', $paymentConfig->gateway);
        $this->assertEquals($config, $paymentConfig->config);
    }
    
    /**
     * 测试创建PayPal配置时缺少必填字段
     * 验证需求: 9.3, 9.4
     */
    public function testSavePaypalConfigWithMissingFields(): void
    {
        $config = [
            'client_id' => 'AeB1234567890abcdefghijklmnopqrstuvwxyz',
            // 缺少 secret 和 notify_url
        ];
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('缺少必填字段');
        
        $this->service->saveConfig('paypal', $config);
    }
    
    /**
     * 测试不支持的支付网关
     * 验证需求: 9.4
     */
    public function testSaveConfigWithUnsupportedGateway(): void
    {
        $config = [
            'some_key' => 'some_value'
        ];
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('不支持的支付网关');
        
        $this->service->saveConfig('unsupported_gateway', $config);
    }
    
    /**
     * 测试更新已存在的支付配置
     * 验证需求: 9.4
     */
    public function testUpdateExistingConfig(): void
    {
        // 创建初始配置
        $initialConfig = [
            'app_id' => '2021001234567890',
            'private_key' => 'initial_private_key',
            'public_key' => 'initial_public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $this->service->saveConfig('alipay', $initialConfig);
        
        // 更新配置
        $updatedConfig = [
            'app_id' => '2021009876543210',
            'private_key' => 'updated_private_key',
            'public_key' => 'updated_public_key',
            'notify_url' => 'https://example.com/notify/alipay/v2'
        ];
        
        $paymentConfig = $this->service->saveConfig('alipay', $updatedConfig);
        
        $this->assertEquals('alipay', $paymentConfig->gateway);
        $this->assertEquals($updatedConfig, $paymentConfig->config);
        
        // 验证数据库中只有一条记录
        $count = PaymentConfig::where('gateway', 'alipay')->count();
        $this->assertEquals(1, $count);
    }
    
    /**
     * 测试获取支付配置
     */
    public function testGetConfig(): void
    {
        // 创建配置
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $this->service->saveConfig('alipay', $config);
        
        // 获取配置
        $paymentConfig = $this->service->getConfig('alipay');
        
        $this->assertNotNull($paymentConfig);
        $this->assertEquals('alipay', $paymentConfig->gateway);
        $this->assertEquals($config, $paymentConfig->config);
    }
    
    /**
     * 测试获取不存在的支付配置
     */
    public function testGetNonExistentConfig(): void
    {
        $paymentConfig = $this->service->getConfig('alipay');
        
        $this->assertNull($paymentConfig);
    }
    
    /**
     * 测试获取所有支付配置
     */
    public function testGetAllConfigs(): void
    {
        // 创建多个配置
        $this->service->saveConfig('alipay', [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ]);
        
        $this->service->saveConfig('wechat', [
            'app_id' => 'wx1234567890abcdef',
            'mch_id' => '1234567890',
            'api_key' => 'api_key',
            'notify_url' => 'https://example.com/notify/wechat'
        ]);
        
        $configs = $this->service->getAllConfigs();
        
        $this->assertCount(2, $configs);
    }
    
    /**
     * 测试获取所有启用的支付配置
     * 验证需求: 9.5
     */
    public function testGetActiveConfigs(): void
    {
        // 创建启用的配置
        $this->service->saveConfig('alipay', [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ], 1);
        
        // 创建禁用的配置
        $this->service->saveConfig('wechat', [
            'app_id' => 'wx1234567890abcdef',
            'mch_id' => '1234567890',
            'api_key' => 'api_key',
            'notify_url' => 'https://example.com/notify/wechat'
        ], 0);
        
        $activeConfigs = $this->service->getActiveConfigs();
        
        $this->assertCount(1, $activeConfigs);
        $this->assertEquals('alipay', $activeConfigs[0]['gateway']);
    }
    
    /**
     * 测试更新支付配置状态
     * 验证需求: 9.5, 9.6
     */
    public function testUpdateStatus(): void
    {
        // 创建配置
        $this->service->saveConfig('alipay', [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ], 1);
        
        // 禁用配置
        $paymentConfig = $this->service->updateStatus('alipay', 0);
        
        $this->assertEquals(0, $paymentConfig->status);
        
        // 启用配置
        $paymentConfig = $this->service->updateStatus('alipay', 1);
        
        $this->assertEquals(1, $paymentConfig->status);
    }
    
    /**
     * 测试更新不存在的支付配置状态
     * 验证需求: 9.5, 9.6
     */
    public function testUpdateStatusOfNonExistentConfig(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('支付配置不存在');
        
        $this->service->updateStatus('alipay', 0);
    }
    
    /**
     * 测试更新支付配置状态时状态值无效
     * 验证需求: 9.5, 9.6
     */
    public function testUpdateStatusWithInvalidValue(): void
    {
        // 创建配置
        $this->service->saveConfig('alipay', [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus('alipay', 2);
    }
    
    /**
     * 测试删除支付配置
     */
    public function testDeleteConfig(): void
    {
        // 创建配置
        $this->service->saveConfig('alipay', [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ]);
        
        // 删除配置
        $result = $this->service->deleteConfig('alipay');
        
        $this->assertTrue($result);
        
        // 验证配置已被删除
        $paymentConfig = $this->service->getConfig('alipay');
        $this->assertNull($paymentConfig);
    }
    
    /**
     * 测试删除不存在的支付配置
     */
    public function testDeleteNonExistentConfig(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('支付配置不存在');
        
        $this->service->deleteConfig('alipay');
    }
    
    /**
     * 测试保存配置时状态值无效
     */
    public function testSaveConfigWithInvalidStatus(): void
    {
        $config = [
            'app_id' => '2021001234567890',
            'private_key' => 'private_key',
            'public_key' => 'public_key',
            'notify_url' => 'https://example.com/notify/alipay'
        ];
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->saveConfig('alipay', $config, 2);
    }
}
