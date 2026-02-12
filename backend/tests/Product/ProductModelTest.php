<?php

namespace Tests\Product;

use PHPUnit\Framework\TestCase;
use app\model\Product;
use app\model\Category;
use think\facade\Db;
use PDO;

/**
 * 商品模型测试
 * 
 * 验证需求: 3.1
 */
class ProductModelTest extends TestCase
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
        // 清空商品表和分类表
        self::$pdo->exec("TRUNCATE TABLE products");
        self::$pdo->exec("TRUNCATE TABLE categories");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // 创建测试分类
        Category::create([
            'name' => '测试分类',
            'sort_order' => 1,
            'status' => 1
        ]);
    }
    
    /**
     * 测试创建商品
     */
    public function testCreateProduct(): void
    {
        $category = Category::where('name', '测试分类')->find();
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => '五帝钱',
            'description' => '招财辟邪',
            'price' => 88.00,
            'stock' => 100,
            'status' => 'on_sale'
        ]);
        
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('五帝钱', $product->name);
        $this->assertEquals(88.00, $product->price);
        $this->assertEquals(100, $product->stock);
        $this->assertEquals('on_sale', $product->status);
    }
    
    /**
     * 测试商品软删除
     */
    public function testSoftDeleteProduct(): void
    {
        $category = Category::where('name', '测试分类')->find();
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => '桃木剑',
            'price' => 168.00,
            'stock' => 50,
            'status' => 'on_sale'
        ]);
        
        $id = $product->id;
        
        // 软删除
        $product->delete();
        
        // 验证商品仍然存在于数据库中
        $stmt = self::$pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $deletedProduct = $stmt->fetch();
        
        $this->assertNotNull($deletedProduct);
        $this->assertNotNull($deletedProduct['deleted_at']);
    }
    
    /**
     * 测试isOnSale方法
     */
    public function testIsOnSale(): void
    {
        $category = Category::where('name', '测试分类')->find();
        
        $onSaleProduct = Product::create([
            'category_id' => $category->id,
            'name' => '罗盘',
            'price' => 288.00,
            'stock' => 30,
            'status' => 'on_sale'
        ]);
        
        $offSaleProduct = Product::create([
            'category_id' => $category->id,
            'name' => '香炉',
            'price' => 128.00,
            'stock' => 20,
            'status' => 'off_sale'
        ]);
        
        $this->assertTrue($onSaleProduct->isOnSale());
        $this->assertFalse($offSaleProduct->isOnSale());
    }
    
    /**
     * 测试自动时间戳
     */
    public function testAutoTimestamp(): void
    {
        $category = Category::where('name', '测试分类')->find();
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => '佛珠',
            'price' => 58.00,
            'stock' => 200,
            'status' => 'on_sale'
        ]);
        
        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
    }
    
    /**
     * 测试JSON类型转换
     */
    public function testJsonTypeConversion(): void
    {
        $category = Category::where('name', '测试分类')->find();
        
        $images = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg'
        ];
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => '符咒',
            'price' => 38.00,
            'stock' => 500,
            'images' => $images,
            'status' => 'on_sale'
        ]);
        
        $this->assertIsArray($product->images);
        $this->assertEquals($images, $product->images);
    }
}
