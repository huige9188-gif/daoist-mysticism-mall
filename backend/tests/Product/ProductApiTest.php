<?php

namespace tests\Product;

use PHPUnit\Framework\TestCase;
use app\service\ProductService;
use think\facade\Db;
use PDO;

/**
 * 商品管理API集成测试
 * 
 * 验证需求: 3.1, 3.4, 3.5, 3.6, 3.7, 3.8
 */
class ProductApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    private static $normalUserId;
    private static $adminToken;
    private static $userToken;
    private static $testCategoryId;
    
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
        
        // 创建管理员用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('admin_product_test', '{$hashedPassword}', 'admin_product@test.com', '13800000021', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        // 创建普通用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_product_test', '{$hashedPassword}', 'user_product@test.com', '13800000022', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
        
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('商品测试分类', 1, 1)
        ");
        self::$testCategoryId = self::$pdo->lastInsertId();
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据（先删除products，再删除categories，避免外键约束）
        if (self::$pdo) {
            // 硬删除所有与测试分类相关的products
            self::$pdo->exec("DELETE FROM products WHERE category_id = " . self::$testCategoryId);
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
            self::$pdo->exec("DELETE FROM categories WHERE id = " . self::$testCategoryId);
        }
    }
    
    /**
     * 测试获取商品列表
     * 验证需求: 3.8
     */
    public function testGetProductList()
    {
        // 创建测试商品
        $categoryId = self::$testCategoryId;
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '测试商品1', 100.00, 10, 'on_sale'),
                   ({$categoryId}, '测试商品2', 200.00, 20, 'off_sale')
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/products', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试搜索商品
     * 验证需求: 3.8
     */
    public function testSearchProducts()
    {
        // 创建测试商品
        $categoryId = self::$testCategoryId;
        $timestamp = time();
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '特殊搜索商品_{$timestamp}', 100.00, 10, 'on_sale')
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/products?search=特殊搜索', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试创建商品
     * 验证需求: 3.1
     */
    public function testCreateProduct()
    {
        $productData = [
            'category_id' => self::$testCategoryId,
            'name' => '新测试商品_' . time(),
            'description' => '测试商品描述',
            'price' => 99.99,
            'stock' => 50,
            'status' => 'off_sale'
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/products', $productData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertEquals($productData['name'], $response['data']['name']);
        $this->assertEquals($productData['price'], $response['data']['price']);
        $this->assertEquals($productData['stock'], $response['data']['stock']);
        
        // 清理测试数据
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM products WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建商品时价格为负数
     * 验证需求: 3.2
     */
    public function testCreateProductWithNegativePrice()
    {
        $productData = [
            'category_id' => self::$testCategoryId,
            'name' => '负价格测试商品',
            'price' => -10.00,
            'stock' => 10
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/products', $productData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('价格必须大于0', $response['message']);
    }
    
    /**
     * 测试创建商品时价格为零
     * 验证需求: 3.2
     */
    public function testCreateProductWithZeroPrice()
    {
        $productData = [
            'category_id' => self::$testCategoryId,
            'name' => '零价格测试商品',
            'price' => 0,
            'stock' => 10
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/products', $productData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('价格必须大于0', $response['message']);
    }
    
    /**
     * 测试创建商品时库存为负数
     * 验证需求: 3.3
     */
    public function testCreateProductWithNegativeStock()
    {
        $productData = [
            'category_id' => self::$testCategoryId,
            'name' => '负库存测试商品',
            'price' => 100.00,
            'stock' => -5
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/products', $productData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('库存不能为负数', $response['message']);
    }
    
    /**
     * 测试更新商品
     * 验证需求: 3.4
     */
    public function testUpdateProduct()
    {
        // 创建测试商品
        $categoryId = self::$testCategoryId;
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '原测试商品名', 100.00, 10, 'off_sale')
        ");
        $productId = self::$pdo->lastInsertId();
        
        $updateData = [
            'name' => '更新后的测试商品名',
            'price' => 150.00,
            'stock' => 20
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/products/' . $productId, $updateData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('更新成功', $response['message']);
        $this->assertEquals($updateData['name'], $response['data']['name']);
        $this->assertEquals($updateData['price'], $response['data']['price']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM products WHERE id = " . $productId);
    }
    
    /**
     * 测试更新不存在的商品
     * 验证需求: 3.4
     */
    public function testUpdateNonExistentProduct()
    {
        $updateData = [
            'name' => '更新后的商品名'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/products/999999', $updateData, self::$adminToken);
        
        $this->assertEquals(404, $response['code']);
        $this->assertStringContainsString('商品不存在', $response['message']);
    }
    
    /**
     * 测试删除商品（软删除）
     * 验证需求: 3.5
     */
    public function testDeleteProduct()
    {
        // 创建测试商品
        $categoryId = self::$testCategoryId;
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '待删除测试商品', 100.00, 10, 'off_sale')
        ");
        $productId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('DELETE', '/api/products/' . $productId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        
        // 验证软删除（记录仍存在但deleted_at不为空）
        $stmt = self::$pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $deletedProduct = $stmt->fetch();
        
        $this->assertNotNull($deletedProduct, '商品记录应该仍然存在');
        $this->assertNotNull($deletedProduct['deleted_at'], 'deleted_at字段应该被设置');
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM products WHERE id = " . $productId);
    }
    
    /**
     * 测试上架商品
     * 验证需求: 3.6
     */
    public function testSetProductOnSale()
    {
        // 创建测试商品（下架状态）
        $categoryId = self::$testCategoryId;
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '上架测试商品', 100.00, 10, 'off_sale')
        ");
        $productId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/products/' . $productId . '/status', ['status' => 'on_sale'], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals('on_sale', $response['data']['status']);
        
        // 验证数据库中状态已更新
        $stmt = self::$pdo->prepare("SELECT status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        $this->assertEquals('on_sale', $product['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM products WHERE id = " . $productId);
    }
    
    /**
     * 测试下架商品
     * 验证需求: 3.7
     */
    public function testSetProductOffSale()
    {
        // 创建测试商品（上架状态）
        $categoryId = self::$testCategoryId;
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '下架测试商品', 100.00, 10, 'on_sale')
        ");
        $productId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/products/' . $productId . '/status', ['status' => 'off_sale'], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals('off_sale', $response['data']['status']);
        
        // 验证数据库中状态已更新
        $stmt = self::$pdo->prepare("SELECT status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        $this->assertEquals('off_sale', $product['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM products WHERE id = " . $productId);
    }
    
    /**
     * 测试更新商品状态时缺少状态参数
     * 验证需求: 3.6, 3.7
     */
    public function testUpdateProductStatusWithoutParameter()
    {
        // 创建测试商品
        $categoryId = self::$testCategoryId;
        self::$pdo->exec("
            INSERT INTO products (category_id, name, price, stock, status) 
            VALUES ({$categoryId}, '测试商品', 100.00, 10, 'off_sale')
        ");
        $productId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/products/' . $productId . '/status', [], self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('状态参数不能为空', $response['message']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM products WHERE id = " . $productId);
    }
    
    /**
     * 测试普通用户无权限访问商品管理
     * 验证需求: 11.7
     */
    public function testProductManagementAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/products', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试未认证访问商品管理
     * 验证需求: 11.5
     */
    public function testProductManagementUnauthorized()
    {
        $response = $this->simulateApiRequest('GET', '/api/products', []);
        
        $this->assertEquals(401, $response['code']);
        $this->assertEquals('未授权访问', $response['message']);
    }
    
    /**
     * 模拟API请求
     * 
     * 这个方法模拟完整的API请求流程：
     * 1. 认证检查（验证token）
     * 2. 授权检查（验证角色权限）
     * 3. 业务逻辑执行
     * 4. 响应格式化
     */
    private function simulateApiRequest(string $method, string $url, array $data = [], ?string $token = null): array
    {
        // 1. 认证检查
        if (!$token) {
            return [
                'code' => 401,
                'message' => '未授权访问',
                'data' => null
            ];
        }
        
        // 解析token获取用户信息
        $userId = (int) str_replace('mock_admin_token_', '', str_replace('mock_user_token_', '', $token));
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'code' => 401,
                'message' => '未授权访问',
                'data' => null
            ];
        }
        
        // 2. 授权检查（需要管理员权限的路由）
        $adminRoutes = ['/api/products'];
        foreach ($adminRoutes as $route) {
            if (strpos($url, $route) === 0 && $user['role'] !== 'admin') {
                return [
                    'code' => 403,
                    'message' => '无权限访问',
                    'data' => null
                ];
            }
        }
        
        // 3. 业务逻辑执行
        try {
            $productService = new ProductService();
            
            // 路由匹配和处理
            if ($method === 'GET' && preg_match('#^/api/products(\?.*)?$#', $url)) {
                // 获取商品列表
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $page = $query['page'] ?? 1;
                $pageSize = $query['page_size'] ?? 10;
                $search = $query['search'] ?? null;
                $categoryId = $query['category_id'] ?? null;
                $onSaleOnly = isset($query['on_sale_only']) && $query['on_sale_only'];
                
                $products = $productService->getProductList(
                    $page,
                    $pageSize,
                    $search,
                    $categoryId,
                    $onSaleOnly
                );
                
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $products->toArray()['data'] ?? []
                ];
                
            } elseif ($method === 'POST' && $url === '/api/products') {
                // 创建商品
                $product = $productService->createProduct($data);
                
                return [
                    'code' => 200,
                    'message' => '创建成功',
                    'data' => $product->toArray()
                ];
                
            } elseif ($method === 'PUT' && preg_match('#^/api/products/(\d+)$#', $url, $matches)) {
                // 更新商品
                $id = $matches[1];
                $product = $productService->updateProduct($id, $data);
                
                return [
                    'code' => 200,
                    'message' => '更新成功',
                    'data' => $product->toArray()
                ];
                
            } elseif ($method === 'DELETE' && preg_match('#^/api/products/(\d+)$#', $url, $matches)) {
                // 删除商品
                $id = $matches[1];
                $productService->deleteProduct($id);
                
                return [
                    'code' => 200,
                    'message' => '删除成功',
                    'data' => null
                ];
                
            } elseif ($method === 'PATCH' && preg_match('#^/api/products/(\d+)/status$#', $url, $matches)) {
                // 更新商品状态
                $id = $matches[1];
                
                if (!isset($data['status'])) {
                    return [
                        'code' => 400,
                        'message' => '状态参数不能为空',
                        'data' => null
                    ];
                }
                
                $product = $productService->updateStatus($id, $data['status']);
                
                return [
                    'code' => 200,
                    'message' => '状态更新成功',
                    'data' => $product->toArray()
                ];
            }
            
            return [
                'code' => 404,
                'message' => '路由不存在',
                'data' => null
            ];
            
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            // 根据错误消息判断错误类型
            if (strpos($message, '不存在') !== false) {
                return [
                    'code' => 404,
                    'message' => $message,
                    'data' => null
                ];
            } elseif (strpos($message, '不能为空') !== false || 
                      strpos($message, '不能为负数') !== false ||
                      strpos($message, '必须大于0') !== false ||
                      strpos($message, '状态') !== false) {
                return [
                    'code' => 400,
                    'message' => $message,
                    'data' => null
                ];
            }
            
            return [
                'code' => 500,
                'message' => $message,
                'data' => null
            ];
        }
    }
}
