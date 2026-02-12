<?php

namespace Tests\FengShuiMaster;

use PHPUnit\Framework\TestCase;
use app\model\FengShuiMaster;
use app\service\FengShuiMasterService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 风水师服务测试
 * 
 * 验证需求: 8.1, 8.2, 8.3, 8.4, 8.5
 */
class FengShuiMasterServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private FengShuiMasterService $service;
    
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
        $this->service = new FengShuiMasterService();
        
        // 清空风水师表
        self::$pdo->exec("TRUNCATE TABLE feng_shui_masters");
    }
    
    /**
     * 测试创建风水师成功
     * 验证需求: 8.1
     */
    public function testCreateFengShuiMasterSuccess(): void
    {
        $data = [
            'name' => '李道长',
            'bio' => '精通风水堪舆，从业30年',
            'specialty' => '阳宅风水、阴宅风水、八字命理',
            'contact' => '13800138000',
            'avatar' => 'https://example.com/images/master-li.jpg',
            'status' => 1
        ];
        
        $master = $this->service->createFengShuiMaster($data);
        
        $this->assertInstanceOf(FengShuiMaster::class, $master);
        $this->assertEquals('李道长', $master->name);
        $this->assertEquals('精通风水堪舆，从业30年', $master->bio);
        $this->assertEquals('阳宅风水、阴宅风水、八字命理', $master->specialty);
        $this->assertEquals('13800138000', $master->contact);
        $this->assertEquals(1, $master->status);
    }
    
    /**
     * 测试创建风水师时姓名为空
     * 验证需求: 8.2
     */
    public function testCreateFengShuiMasterWithEmptyName(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('风水师姓名不能为空');
        
        $this->service->createFengShuiMaster([
            'name' => '',
            'bio' => '测试简介'
        ]);
    }
    
    /**
     * 测试创建风水师时姓名为空白字符
     * 验证需求: 8.2
     */
    public function testCreateFengShuiMasterWithWhitespaceName(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('风水师姓名不能为空');
        
        $this->service->createFengShuiMaster([
            'name' => '   ',
            'bio' => '测试简介'
        ]);
    }
    
    /**
     * 测试创建风水师时使用默认状态
     * 验证需求: 8.1
     */
    public function testCreateFengShuiMasterWithDefaultStatus(): void
    {
        $master = $this->service->createFengShuiMaster([
            'name' => '王大师',
            'bio' => '风水大师'
        ]);
        
        $this->assertEquals(1, $master->status);
    }
    
    /**
     * 测试创建风水师时只填写必填字段
     * 验证需求: 8.1
     */
    public function testCreateFengShuiMasterWithMinimalData(): void
    {
        $master = $this->service->createFengShuiMaster([
            'name' => '张真人'
        ]);
        
        $this->assertInstanceOf(FengShuiMaster::class, $master);
        $this->assertEquals('张真人', $master->name);
        $this->assertEquals(1, $master->status);
    }
    
    /**
     * 测试更新风水师成功
     * 验证需求: 8.3
     */
    public function testUpdateFengShuiMasterSuccess(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '刘道长',
            'bio' => '初级风水师',
            'specialty' => '阳宅风水',
            'contact' => '13900139000'
        ]);
        
        // 更新风水师
        $updatedMaster = $this->service->updateFengShuiMaster($master->id, [
            'name' => '刘大师',
            'bio' => '高级风水师，从业20年',
            'specialty' => '阳宅风水、阴宅风水、奇门遁甲',
            'contact' => '13900139001'
        ]);
        
        $this->assertEquals('刘大师', $updatedMaster->name);
        $this->assertEquals('高级风水师，从业20年', $updatedMaster->bio);
        $this->assertEquals('阳宅风水、阴宅风水、奇门遁甲', $updatedMaster->specialty);
        $this->assertEquals('13900139001', $updatedMaster->contact);
    }
    
    /**
     * 测试更新不存在的风水师
     * 验证需求: 8.3
     */
    public function testUpdateNonExistentFengShuiMaster(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('风水师不存在');
        
        $this->service->updateFengShuiMaster(99999, [
            'name' => '测试大师'
        ]);
    }
    
    /**
     * 测试更新风水师时姓名为空
     * 验证需求: 8.2
     */
    public function testUpdateFengShuiMasterWithEmptyName(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '陈道长',
            'bio' => '风水师'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('风水师姓名不能为空');
        
        $this->service->updateFengShuiMaster($master->id, [
            'name' => ''
        ]);
    }
    
    /**
     * 测试更新风水师时姓名为空白字符
     * 验证需求: 8.2
     */
    public function testUpdateFengShuiMasterWithWhitespaceName(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '赵道长',
            'bio' => '风水师'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('风水师姓名不能为空');
        
        $this->service->updateFengShuiMaster($master->id, [
            'name' => '   '
        ]);
    }
    
    /**
     * 测试删除风水师成功
     * 验证需求: 8.4
     */
    public function testDeleteFengShuiMasterSuccess(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '孙道长',
            'bio' => '风水师'
        ]);
        
        // 删除风水师
        $result = $this->service->deleteFengShuiMaster($master->id);
        $this->assertTrue($result);
        
        // 验证风水师仍然存在于数据库中（软删除）
        $stmt = self::$pdo->prepare("SELECT * FROM feng_shui_masters WHERE id = :id");
        $stmt->execute(['id' => $master->id]);
        $deletedMaster = $stmt->fetch();
        
        $this->assertNotNull($deletedMaster);
        $this->assertNotNull($deletedMaster['deleted_at']);
    }
    
    /**
     * 测试删除不存在的风水师
     * 验证需求: 8.4
     */
    public function testDeleteNonExistentFengShuiMaster(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('风水师不存在');
        
        $this->service->deleteFengShuiMaster(99999);
    }
    
    /**
     * 测试更新风水师状态为启用
     * 验证需求: 8.5
     */
    public function testUpdateFengShuiMasterStatusToEnabled(): void
    {
        // 创建风水师（禁用）
        $master = $this->service->createFengShuiMaster([
            'name' => '周道长',
            'bio' => '风水师',
            'status' => 0
        ]);
        
        // 启用风水师
        $updatedMaster = $this->service->updateStatus($master->id, 1);
        
        $this->assertEquals(1, $updatedMaster->status);
    }
    
    /**
     * 测试更新风水师状态为禁用
     * 验证需求: 8.5
     */
    public function testUpdateFengShuiMasterStatusToDisabled(): void
    {
        // 创建风水师（启用）
        $master = $this->service->createFengShuiMaster([
            'name' => '吴道长',
            'bio' => '风水师',
            'status' => 1
        ]);
        
        // 禁用风水师
        $updatedMaster = $this->service->updateStatus($master->id, 0);
        
        $this->assertEquals(0, $updatedMaster->status);
    }
    
    /**
     * 测试更新风水师状态时状态值无效
     * 验证需求: 8.5
     */
    public function testUpdateFengShuiMasterStatusWithInvalidValue(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '郑道长',
            'bio' => '风水师'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus($master->id, 2);
    }
    
    /**
     * 测试更新不存在的风水师状态
     * 验证需求: 8.5
     */
    public function testUpdateNonExistentFengShuiMasterStatus(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('风水师不存在');
        
        $this->service->updateStatus(99999, 1);
    }
    
    /**
     * 测试获取风水师列表
     */
    public function testGetFengShuiMasterList(): void
    {
        // 创建多个风水师
        $this->service->createFengShuiMaster([
            'name' => '风水师A',
            'bio' => '简介A',
            'status' => 1
        ]);
        
        $this->service->createFengShuiMaster([
            'name' => '风水师B',
            'bio' => '简介B',
            'status' => 1
        ]);
        
        $this->service->createFengShuiMaster([
            'name' => '风水师C',
            'bio' => '简介C',
            'status' => 0
        ]);
        
        // 获取风水师列表
        $result = $this->service->getFengShuiMasterList(1, 10);
        
        $this->assertCount(3, $result->items());
    }
    
    /**
     * 测试只获取启用的风水师
     * 验证需求: 8.5
     */
    public function testGetEnabledFengShuiMastersOnly(): void
    {
        // 创建启用和禁用的风水师
        $this->service->createFengShuiMaster([
            'name' => '启用风水师1',
            'bio' => '简介1',
            'status' => 1
        ]);
        
        $this->service->createFengShuiMaster([
            'name' => '禁用风水师',
            'bio' => '简介',
            'status' => 0
        ]);
        
        $this->service->createFengShuiMaster([
            'name' => '启用风水师2',
            'bio' => '简介2',
            'status' => 1
        ]);
        
        // 只获取启用的风水师
        $result = $this->service->getFengShuiMasterList(1, 10, true);
        
        $this->assertCount(2, $result->items());
        foreach ($result->items() as $master) {
            $this->assertEquals(1, $master->status);
        }
    }
    
    /**
     * 测试根据ID获取风水师
     */
    public function testGetFengShuiMasterById(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '测试大师',
            'bio' => '测试简介'
        ]);
        
        // 根据ID获取风水师
        $foundMaster = $this->service->getFengShuiMasterById($master->id);
        
        $this->assertNotNull($foundMaster);
        $this->assertEquals('测试大师', $foundMaster->name);
    }
    
    /**
     * 测试获取不存在的风水师
     */
    public function testGetNonExistentFengShuiMasterById(): void
    {
        $master = $this->service->getFengShuiMasterById(99999);
        
        $this->assertNull($master);
    }
    
    /**
     * 测试风水师模型的isEnabled方法
     */
    public function testFengShuiMasterIsEnabled(): void
    {
        // 创建启用的风水师
        $enabledMaster = $this->service->createFengShuiMaster([
            'name' => '启用大师',
            'bio' => '简介',
            'status' => 1
        ]);
        
        $this->assertTrue($enabledMaster->isEnabled());
        
        // 创建禁用的风水师
        $disabledMaster = $this->service->createFengShuiMaster([
            'name' => '禁用大师',
            'bio' => '简介',
            'status' => 0
        ]);
        
        $this->assertFalse($disabledMaster->isEnabled());
    }
    
    /**
     * 测试创建风水师时包含所有字段
     * 验证需求: 8.1
     */
    public function testCreateFengShuiMasterWithAllFields(): void
    {
        $data = [
            'name' => '完整信息大师',
            'bio' => '这是一个完整的简介，包含了大师的所有信息',
            'specialty' => '阳宅风水、阴宅风水、八字命理、奇门遁甲、六爻预测',
            'contact' => 'master@example.com, 13800138000',
            'avatar' => 'https://example.com/avatars/master-full.jpg',
            'status' => 1
        ];
        
        $master = $this->service->createFengShuiMaster($data);
        
        $this->assertEquals('完整信息大师', $master->name);
        $this->assertEquals('这是一个完整的简介，包含了大师的所有信息', $master->bio);
        $this->assertEquals('阳宅风水、阴宅风水、八字命理、奇门遁甲、六爻预测', $master->specialty);
        $this->assertEquals('master@example.com, 13800138000', $master->contact);
        $this->assertEquals('https://example.com/avatars/master-full.jpg', $master->avatar);
        $this->assertEquals(1, $master->status);
    }
    
    /**
     * 测试更新风水师的部分字段
     * 验证需求: 8.3
     */
    public function testUpdateFengShuiMasterPartialFields(): void
    {
        // 创建风水师
        $master = $this->service->createFengShuiMaster([
            'name' => '原始名称',
            'bio' => '原始简介',
            'specialty' => '原始专长',
            'contact' => '原始联系方式'
        ]);
        
        // 只更新部分字段
        $updatedMaster = $this->service->updateFengShuiMaster($master->id, [
            'bio' => '更新后的简介'
        ]);
        
        // 验证只有bio被更新，其他字段保持不变
        $this->assertEquals('原始名称', $updatedMaster->name);
        $this->assertEquals('更新后的简介', $updatedMaster->bio);
    }
}
