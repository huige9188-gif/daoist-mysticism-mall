<?php

namespace Tests\Product;

use PHPUnit\Framework\TestCase;
use app\model\Product;
use app\model\Category;
use app\service\ProductService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 商品服务测试
 * 
 * 验证需求: 3.1, 3.4, 3.5, 3.6, 3.7, 3.8
 */
class ProductServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private ProductService $service;
    private ?Category $testCategory = null;
    
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
        $this->service = new ProductService();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空商品表和分类表
        self::$pdo->exec("TRUNCATE TABLE products");
        self::$pdo->exec("TRUNCATE TABLE categories");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // 创建测试分类
        $this->testCategory = Category::create([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
    }
    
    /**
     * 测试创建商品成功
     * 验证需求: 3.1
     */
    public function testCreateProductSuccess(): void
    {
        $data = [
            'category_id' => $this->testCategory->id,
            'name' => '五帝钱',
            'description' => '招财辟邪',
            'price' => 88.00,
            'stock' => 100,
            'status' => 'on_sale'
        ];
        
        $product = $this->service->createProduct($data);
        
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('五帝钱', $product->name);
        $this->assertEquals(88.00, $product->price);
        $this->assertEquals(100, $product->stock);
        $this->assertEquals('on_sale', $product->status);
    }
    
    /**
     * 测试创建商品时价格为零
     * 验证需求: 3.2
     */
    public function testCreateProductWithZeroPrice(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('价格必须大于0');
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => 0,
            'stock' => 10
        ]);
    }
    
    /**
     * 测试创建商品时价格为负数
     * 验证需求: 3.2
     */
    public function testCreateProductWithNegativePrice(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('价格必须大于0');
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => -10.00,
            'stock' => 10
        ]);
    }
    
    /**
     * 测试创建商品时库存为负数
     * 验证需求: 3.3
     */
    public function testCreateProductWithNegativeStock(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('库存不能为负数');
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => -5
        ]);
    }
    
    /**
     * 测试创建商品时分类不存在
     * 验证需求: 3.1
     */
    public function testCreateProductWithNonExistentCategory(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('分类不存在');
        
        $this->service->createProduct([
            'category_id' => 99999,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => 10
        ]);
    }
    
    /**
     * 测试创建商品时使用默认值
     * 验证需求: 3.1
     */
    public function testCreateProductWithDefaults(): void
    {
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '桃木剑',
            'price' => 168.00
        ]);
        
        $this->assertEquals(0, $product->stock);
        $this->assertEquals('off_sale', $product->status);
    }
    
    /**
     * 测试更新商品成功
     * 验证需求: 3.4
     */
    public function testUpdateProductSuccess(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '罗盘',
            'price' => 288.00,
            'stock' => 30
        ]);
        
        // 更新商品
        $updatedProduct = $this->service->updateProduct($product->id, [
            'name' => '专业罗盘',
            'price' => 388.00,
            'stock' => 50
        ]);
        
        $this->assertEquals('专业罗盘', $updatedProduct->name);
        $this->assertEquals(388.00, $updatedProduct->price);
        $this->assertEquals(50, $updatedProduct->stock);
    }
    
    /**
     * 测试更新不存在的商品
     * 验证需求: 3.4
     */
    public function testUpdateNonExistentProduct(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('商品不存在');
        
        $this->service->updateProduct(99999, [
            'name' => '测试商品'
        ]);
    }
    
    /**
     * 测试更新商品时价格为零
     * 验证需求: 3.2
     */
    public function testUpdateProductWithZeroPrice(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '香炉',
            'price' => 128.00,
            'stock' => 20
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('价格必须大于0');
        
        $this->service->updateProduct($product->id, [
            'price' => 0
        ]);
    }
    
    /**
     * 测试更新商品时库存为负数
     * 验证需求: 3.3
     */
    public function testUpdateProductWithNegativeStock(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '佛珠',
            'price' => 58.00,
            'stock' => 200
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('库存不能为负数');
        
        $this->service->updateProduct($product->id, [
            'stock' => -10
        ]);
    }
    
    /**
     * 测试删除商品成功
     * 验证需求: 3.5
     */
    public function testDeleteProductSuccess(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '符咒',
            'price' => 38.00,
            'stock' => 500
        ]);
        
        // 删除商品
        $result = $this->service->deleteProduct($product->id);
        $this->assertTrue($result);
        
        // 验证商品仍然存在于数据库中（软删除）
        $stmt = self::$pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $product->id]);
        $deletedProduct = $stmt->fetch();
        
        $this->assertNotNull($deletedProduct);
        $this->assertNotNull($deletedProduct['deleted_at']);
    }
    
    /**
     * 测试删除不存在的商品
     * 验证需求: 3.5
     */
    public function testDeleteNonExistentProduct(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('商品不存在');
        
        $this->service->deleteProduct(99999);
    }
    
    /**
     * 测试更新商品状态为上架
     * 验证需求: 3.6
     */
    public function testUpdateProductStatusToOnSale(): void
    {
        // 创建商品（默认下架）
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '水晶球',
            'price' => 188.00,
            'stock' => 15
        ]);
        
        // 上架商品
        $updatedProduct = $this->service->updateStatus($product->id, 'on_sale');
        
        $this->assertEquals('on_sale', $updatedProduct->status);
    }
    
    /**
     * 测试更新商品状态为下架
     * 验证需求: 3.7
     */
    public function testUpdateProductStatusToOffSale(): void
    {
        // 创建商品（上架）
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '玉佩',
            'price' => 588.00,
            'stock' => 8,
            'status' => 'on_sale'
        ]);
        
        // 下架商品
        $updatedProduct = $this->service->updateStatus($product->id, 'off_sale');
        
        $this->assertEquals('off_sale', $updatedProduct->status);
    }
    
    /**
     * 测试更新商品状态时状态值无效
     * 验证需求: 3.6, 3.7
     */
    public function testUpdateProductStatusWithInvalidValue(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => 10
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus($product->id, 'invalid_status');
    }
    
    /**
     * 测试获取商品列表
     * 验证需求: 3.8
     */
    public function testGetProductList(): void
    {
        // 创建多个商品
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '商品A',
            'price' => 100.00,
            'stock' => 10,
            'status' => 'on_sale'
        ]);
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '商品B',
            'price' => 200.00,
            'stock' => 20,
            'status' => 'on_sale'
        ]);
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '商品C',
            'price' => 300.00,
            'stock' => 30,
            'status' => 'off_sale'
        ]);
        
        // 获取商品列表
        $result = $this->service->getProductList(1, 10);
        
        $this->assertCount(3, $result->items());
    }
    
    /**
     * 测试搜索商品
     * 验证需求: 3.8
     */
    public function testSearchProducts(): void
    {
        // 创建多个商品
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '五帝钱',
            'price' => 88.00,
            'stock' => 100
        ]);
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '桃木剑',
            'price' => 168.00,
            'stock' => 50
        ]);
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '五行符',
            'price' => 38.00,
            'stock' => 200
        ]);
        
        // 搜索包含"五"的商品
        $result = $this->service->getProductList(1, 10, '五');
        
        $this->assertCount(2, $result->items());
    }
    
    /**
     * 测试只获取上架商品
     * 验证需求: 3.6, 3.7
     */
    public function testGetOnSaleProductsOnly(): void
    {
        // 创建上架和下架的商品
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '上架商品1',
            'price' => 100.00,
            'stock' => 10,
            'status' => 'on_sale'
        ]);
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '下架商品',
            'price' => 200.00,
            'stock' => 20,
            'status' => 'off_sale'
        ]);
        
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '上架商品2',
            'price' => 300.00,
            'stock' => 30,
            'status' => 'on_sale'
        ]);
        
        // 只获取上架商品
        $result = $this->service->getProductList(1, 10, null, null, true);
        
        $this->assertCount(2, $result->items());
        foreach ($result->items() as $product) {
            $this->assertEquals('on_sale', $product->status);
        }
    }
    
    /**
     * 测试按分类筛选商品
     * 验证需求: 3.8
     */
    public function testGetProductsByCategory(): void
    {
        // 创建另一个分类
        $category2 = Category::create([
            'name' => '分类2',
            'sort_order' => 2,
            'status' => 1
        ]);
        
        // 在不同分类下创建商品
        $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '分类1商品',
            'price' => 100.00,
            'stock' => 10
        ]);
        
        $this->service->createProduct([
            'category_id' => $category2->id,
            'name' => '分类2商品',
            'price' => 200.00,
            'stock' => 20
        ]);
        
        // 按分类筛选
        $result = $this->service->getProductList(1, 10, null, $this->testCategory->id);
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('分类1商品', $result->items()[0]->name);
    }
    
    /**
     * 测试减少商品库存
     */
    public function testDecreaseStock(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => 50
        ]);
        
        // 减少库存
        $updatedProduct = $this->service->decreaseStock($product->id, 10);
        
        $this->assertEquals(40, $updatedProduct->stock);
    }
    
    /**
     * 测试减少库存时库存不足
     */
    public function testDecreaseStockInsufficient(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => 5
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('商品库存不足');
        
        $this->service->decreaseStock($product->id, 10);
    }
    
    /**
     * 测试增加商品库存
     */
    public function testIncreaseStock(): void
    {
        // 创建商品
        $product = $this->service->createProduct([
            'category_id' => $this->testCategory->id,
            'name' => '测试商品',
            'price' => 100.00,
            'stock' => 50
        ]);
        
        // 增加库存
        $updatedProduct = $this->service->increaseStock($product->id, 20);
        
        $this->assertEquals(70, $updatedProduct->stock);
    }
}
