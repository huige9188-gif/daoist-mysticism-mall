<?php

namespace Tests\Category;

use PHPUnit\Framework\TestCase;
use app\model\Category;
use think\facade\Db;
use PDO;

/**
 * 商品分类模型测试
 * 
 * 验证需求: 4.1
 */
class CategoryModelTest extends TestCase
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
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空分类表
        self::$pdo->exec("TRUNCATE TABLE categories");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * 测试创建分类
     */
    public function testCreateCategory(): void
    {
        $category = Category::create([
            'name' => '法器',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('法器', $category->name);
        $this->assertEquals(1, $category->sort_order);
        $this->assertEquals(1, $category->status);
    }
    
    /**
     * 测试分类软删除
     */
    public function testSoftDeleteCategory(): void
    {
        $category = Category::create([
            'name' => '符咒',
            'sort_order' => 2,
            'status' => 1
        ]);
        
        $id = $category->id;
        
        // 软删除
        $category->delete();
        
        // 验证分类仍然存在于数据库中
        $stmt = self::$pdo->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $deletedCategory = $stmt->fetch();
        
        $this->assertNotNull($deletedCategory);
        $this->assertNotNull($deletedCategory['deleted_at']);
    }
    
    /**
     * 测试isActive方法
     */
    public function testIsActive(): void
    {
        $activeCategory = Category::create([
            'name' => '香炉',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        $inactiveCategory = Category::create([
            'name' => '佛珠',
            'sort_order' => 2,
            'status' => 0
        ]);
        
        $this->assertTrue($activeCategory->isActive());
        $this->assertFalse($inactiveCategory->isActive());
    }
    
    /**
     * 测试自动时间戳
     */
    public function testAutoTimestamp(): void
    {
        $category = Category::create([
            'name' => '罗盘',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        $this->assertNotNull($category->created_at);
        $this->assertNotNull($category->updated_at);
    }
}
