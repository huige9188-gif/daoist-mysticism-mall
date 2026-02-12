<?php

namespace Tests\Category;

use PHPUnit\Framework\TestCase;
use app\model\Category;
use app\model\Product;
use app\service\CategoryService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 商品分类服务测试
 * 
 * 验证需求: 4.1, 4.3, 4.4, 4.5, 4.6
 */
class CategoryServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private CategoryService $service;
    
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
        $this->service = new CategoryService();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空分类表和商品表
        self::$pdo->exec("TRUNCATE TABLE categories");
        self::$pdo->exec("TRUNCATE TABLE products");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * 测试创建分类成功
     * 验证需求: 4.1
     */
    public function testCreateCategorySuccess(): void
    {
        $data = [
            'name' => '法器',
            'sort_order' => 1,
            'status' => 1
        ];
        
        $category = $this->service->createCategory($data);
        
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('法器', $category->name);
        $this->assertEquals(1, $category->sort_order);
        $this->assertEquals(1, $category->status);
    }
    
    /**
     * 测试创建分类时名称为空
     * 验证需求: 4.2
     */
    public function testCreateCategoryWithEmptyName(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('分类名称不能为空');
        
        $this->service->createCategory([
            'name' => '',
            'sort_order' => 1,
            'status' => 1
        ]);
    }
    
    /**
     * 测试创建分类时名称只有空格
     * 验证需求: 4.2
     */
    public function testCreateCategoryWithWhitespaceName(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('分类名称不能为空');
        
        $this->service->createCategory([
            'name' => '   ',
            'sort_order' => 1,
            'status' => 1
        ]);
    }
    
    /**
     * 测试创建分类时使用默认值
     * 验证需求: 4.1
     */
    public function testCreateCategoryWithDefaults(): void
    {
        $category = $this->service->createCategory([
            'name' => '符咒'
        ]);
        
        $this->assertEquals(0, $category->sort_order);
        $this->assertEquals(1, $category->status);
    }
    
    /**
     * 测试更新分类成功
     * 验证需求: 4.3
     */
    public function testUpdateCategorySuccess(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '香炉',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 更新分类
        $updatedCategory = $this->service->updateCategory($category->id, [
            'name' => '香炉用品',
            'sort_order' => 5
        ]);
        
        $this->assertEquals('香炉用品', $updatedCategory->name);
        $this->assertEquals(5, $updatedCategory->sort_order);
    }
    
    /**
     * 测试更新不存在的分类
     * 验证需求: 4.3
     */
    public function testUpdateNonExistentCategory(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('分类不存在');
        
        $this->service->updateCategory(99999, [
            'name' => '测试分类'
        ]);
    }
    
    /**
     * 测试更新分类时名称为空
     * 验证需求: 4.3
     */
    public function testUpdateCategoryWithEmptyName(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '佛珠',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('分类名称不能为空');
        
        $this->service->updateCategory($category->id, [
            'name' => ''
        ]);
    }
    
    /**
     * 测试删除分类成功
     * 验证需求: 4.4
     */
    public function testDeleteCategorySuccess(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '罗盘',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 删除分类
        $result = $this->service->deleteCategory($category->id);
        $this->assertTrue($result);
        
        // 验证分类仍然存在于数据库中（软删除）
        $stmt = self::$pdo->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $category->id]);
        $deletedCategory = $stmt->fetch();
        
        $this->assertNotNull($deletedCategory);
        $this->assertNotNull($deletedCategory['deleted_at']);
    }
    
    /**
     * 测试删除不存在的分类
     * 验证需求: 4.4
     */
    public function testDeleteNonExistentCategory(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('分类不存在');
        
        $this->service->deleteCategory(99999);
    }
    
    /**
     * 测试删除包含商品的分类
     * 验证需求: 4.4
     * 
     * 注意：此测试需要Product模型，暂时跳过
     */
    public function testDeleteCategoryWithProducts(): void
    {
        if (!class_exists('app\model\Product')) {
            $this->markTestSkipped('Product模型尚未实现，跳过此测试');
        }
        
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '法器',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 创建商品
        Product::create([
            'category_id' => $category->id,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => 10,
            'status' => 'on_sale'
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('该分类下有商品，无法删除');
        
        $this->service->deleteCategory($category->id);
    }
    
    /**
     * 测试获取分类列表（按排序值排序）
     * 验证需求: 4.1, 4.5
     */
    public function testGetCategoryListSorted(): void
    {
        // 创建多个分类（不按顺序）
        $this->service->createCategory([
            'name' => '分类C',
            'sort_order' => 30,
            'status' => 1
        ]);
        
        $this->service->createCategory([
            'name' => '分类A',
            'sort_order' => 10,
            'status' => 1
        ]);
        
        $this->service->createCategory([
            'name' => '分类B',
            'sort_order' => 20,
            'status' => 1
        ]);
        
        // 获取分类列表
        $categories = $this->service->getCategoryList();
        
        $this->assertCount(3, $categories);
        $this->assertEquals('分类A', $categories[0]->name);
        $this->assertEquals('分类B', $categories[1]->name);
        $this->assertEquals('分类C', $categories[2]->name);
    }
    
    /**
     * 测试获取启用的分类列表
     * 验证需求: 4.6
     */
    public function testGetActiveCategoryList(): void
    {
        // 创建启用和禁用的分类
        $this->service->createCategory([
            'name' => '启用分类1',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        $this->service->createCategory([
            'name' => '禁用分类',
            'sort_order' => 2,
            'status' => 0
        ]);
        
        $this->service->createCategory([
            'name' => '启用分类2',
            'sort_order' => 3,
            'status' => 1
        ]);
        
        // 获取启用的分类列表
        $categories = $this->service->getCategoryList(true);
        
        $this->assertCount(2, $categories);
        $this->assertEquals('启用分类1', $categories[0]->name);
        $this->assertEquals('启用分类2', $categories[1]->name);
    }
    
    /**
     * 测试更新分类状态
     * 验证需求: 4.6
     */
    public function testUpdateCategoryStatus(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 禁用分类
        $updatedCategory = $this->service->updateStatus($category->id, 0);
        $this->assertEquals(0, $updatedCategory->status);
        
        // 启用分类
        $updatedCategory = $this->service->updateStatus($category->id, 1);
        $this->assertEquals(1, $updatedCategory->status);
    }
    
    /**
     * 测试更新分类状态时状态值无效
     * 验证需求: 4.6
     */
    public function testUpdateCategoryStatusWithInvalidValue(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus($category->id, 2);
    }
    
    /**
     * 测试更新分类排序值
     * 验证需求: 4.5
     */
    public function testUpdateSortOrder(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 更新排序值
        $updatedCategory = $this->service->updateSortOrder($category->id, 10);
        
        $this->assertEquals(10, $updatedCategory->sort_order);
    }
    
    /**
     * 测试根据ID获取分类
     */
    public function testGetCategoryById(): void
    {
        // 创建分类
        $category = $this->service->createCategory([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
        
        // 获取分类
        $foundCategory = $this->service->getCategoryById($category->id);
        
        $this->assertNotNull($foundCategory);
        $this->assertEquals('测试分类', $foundCategory->name);
    }
}
