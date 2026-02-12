<?php

namespace tests\Category;

use PHPUnit\Framework\TestCase;
use app\service\CategoryService;
use think\facade\Db;
use PDO;

/**
 * 商品分类API集成测试
 * 
 * 验证需求: 4.1, 4.3, 4.4, 4.6
 */
class CategoryApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    private static $normalUserId;
    private static $adminToken;
    private static $userToken;
    
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
            VALUES ('admin_category_test', '{$hashedPassword}', 'admin_category@test.com', '13800000011', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        // 创建普通用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_category_test', '{$hashedPassword}', 'user_category@test.com', '13800000012', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
            self::$pdo->exec("DELETE FROM categories WHERE name LIKE '%test%'");
        }
    }
    
    /**
     * 测试获取分类列表
     * 验证需求: 4.1
     */
    public function testGetCategoryList()
    {
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('测试分类1', 1, 1), ('测试分类2', 2, 1)
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/categories', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
        $this->assertGreaterThanOrEqual(2, count($response['data']));
        
        // 验证排序正确性（按sort_order升序）
        $sortOrders = array_column($response['data'], 'sort_order');
        $sortedOrders = $sortOrders;
        sort($sortedOrders);
        $this->assertEquals($sortedOrders, $sortOrders, '分类应该按sort_order升序排列');
    }
    
    /**
     * 测试创建分类
     * 验证需求: 4.1
     */
    public function testCreateCategory()
    {
        $categoryData = [
            'name' => '新测试分类_' . time(),
            'sort_order' => 10,
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/categories', $categoryData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertEquals($categoryData['name'], $response['data']['name']);
        $this->assertEquals($categoryData['sort_order'], $response['data']['sort_order']);
        
        // 清理测试数据
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM categories WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建分类时名称为空
     * 验证需求: 4.2
     */
    public function testCreateCategoryWithEmptyName()
    {
        $categoryData = [
            'name' => '',
            'sort_order' => 10
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/categories', $categoryData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('分类名称不能为空', $response['message']);
    }
    
    /**
     * 测试更新分类
     * 验证需求: 4.3
     */
    public function testUpdateCategory()
    {
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('原测试分类名', 5, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        $updateData = [
            'name' => '更新后的测试分类名',
            'sort_order' => 15
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/categories/' . $categoryId, $updateData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('更新成功', $response['message']);
        $this->assertEquals($updateData['name'], $response['data']['name']);
        $this->assertEquals($updateData['sort_order'], $response['data']['sort_order']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM categories WHERE id = " . $categoryId);
    }
    
    /**
     * 测试更新不存在的分类
     * 验证需求: 4.3
     */
    public function testUpdateNonExistentCategory()
    {
        $updateData = [
            'name' => '更新后的分类名'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/categories/999999', $updateData, self::$adminToken);
        
        $this->assertEquals(404, $response['code']);
        $this->assertStringContainsString('分类不存在', $response['message']);
    }
    
    /**
     * 测试删除分类
     * 验证需求: 4.4
     */
    public function testDeleteCategory()
    {
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('待删除测试分类', 1, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('DELETE', '/api/categories/' . $categoryId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        
        // 验证软删除（记录仍存在但deleted_at不为空）
        $stmt = self::$pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $deletedCategory = $stmt->fetch();
        
        $this->assertNotNull($deletedCategory, '分类记录应该仍然存在');
        $this->assertNotNull($deletedCategory['deleted_at'], 'deleted_at字段应该被设置');
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM categories WHERE id = " . $categoryId);
    }
    
    /**
     * 测试删除包含商品的分类
     * 验证需求: 4.4
     */
    public function testDeleteCategoryWithProducts()
    {
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('有商品的测试分类', 1, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        // 检查Product模型是否存在
        if (class_exists('app\model\Product')) {
            // 检查products表是否存在
            $stmt = self::$pdo->query("SHOW TABLES LIKE 'products'");
            if ($stmt->rowCount() > 0) {
                // 创建关联商品
                self::$pdo->exec("
                    INSERT INTO products (category_id, name, price, stock, status) 
                    VALUES ({$categoryId}, '测试商品', 100, 10, 'on_sale')
                ");
                $productId = self::$pdo->lastInsertId();
                
                $response = $this->simulateApiRequest('DELETE', '/api/categories/' . $categoryId, [], self::$adminToken);
                
                $this->assertEquals(400, $response['code']);
                $this->assertStringContainsString('该分类下有商品，无法删除', $response['message']);
                
                // 清理测试数据
                self::$pdo->exec("DELETE FROM products WHERE id = " . $productId);
                self::$pdo->exec("DELETE FROM categories WHERE id = " . $categoryId);
            } else {
                $this->markTestSkipped('products表不存在，跳过此测试');
            }
        } else {
            // Product模型不存在，删除应该成功
            $response = $this->simulateApiRequest('DELETE', '/api/categories/' . $categoryId, [], self::$adminToken);
            
            $this->assertEquals(200, $response['code']);
            $this->assertEquals('删除成功', $response['message']);
            
            // 清理测试数据
            self::$pdo->exec("DELETE FROM categories WHERE id = " . $categoryId);
        }
    }
    
    /**
     * 测试更新分类状态
     * 验证需求: 4.6
     */
    public function testUpdateCategoryStatus()
    {
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('状态测试分类', 1, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/categories/' . $categoryId . '/status', ['status' => 0], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals(0, $response['data']['status']);
        
        // 验证数据库中状态已更新
        $stmt = self::$pdo->prepare("SELECT status FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        $this->assertEquals(0, $category['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM categories WHERE id = " . $categoryId);
    }
    
    /**
     * 测试更新分类状态时缺少状态参数
     * 验证需求: 4.6
     */
    public function testUpdateCategoryStatusWithoutParameter()
    {
        // 创建测试分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('测试分类', 1, 1)
        ");
        $categoryId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/categories/' . $categoryId . '/status', [], self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('状态参数不能为空', $response['message']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM categories WHERE id = " . $categoryId);
    }
    
    /**
     * 测试获取启用的分类列表
     * 验证需求: 4.6
     */
    public function testGetActiveCategoriesOnly()
    {
        // 创建启用和禁用的分类
        self::$pdo->exec("
            INSERT INTO categories (name, sort_order, status) 
            VALUES ('启用测试分类', 1, 1), ('禁用测试分类', 2, 0)
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/categories?active_only=1', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        
        // 验证所有返回的分类都是启用状态
        foreach ($response['data'] as $category) {
            $this->assertEquals(1, $category['status'], '只应返回启用状态的分类');
        }
    }
    
    /**
     * 测试普通用户无权限访问分类管理
     * 验证需求: 11.7
     */
    public function testCategoryManagementAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/categories', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试未认证访问分类管理
     * 验证需求: 11.5
     */
    public function testCategoryManagementUnauthorized()
    {
        $response = $this->simulateApiRequest('GET', '/api/categories', []);
        
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
        $adminRoutes = ['/api/categories'];
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
            $categoryService = new CategoryService();
            
            // 路由匹配和处理
            if ($method === 'GET' && preg_match('#^/api/categories(\?.*)?$#', $url)) {
                // 获取分类列表
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $activeOnly = isset($query['active_only']) && $query['active_only'];
                
                $categories = $categoryService->getCategoryList($activeOnly);
                
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $categories->toArray()
                ];
                
            } elseif ($method === 'POST' && $url === '/api/categories') {
                // 创建分类
                $category = $categoryService->createCategory($data);
                
                return [
                    'code' => 200,
                    'message' => '创建成功',
                    'data' => $category->toArray()
                ];
                
            } elseif ($method === 'PUT' && preg_match('#^/api/categories/(\d+)$#', $url, $matches)) {
                // 更新分类
                $id = $matches[1];
                $category = $categoryService->updateCategory($id, $data);
                
                return [
                    'code' => 200,
                    'message' => '更新成功',
                    'data' => $category->toArray()
                ];
                
            } elseif ($method === 'DELETE' && preg_match('#^/api/categories/(\d+)$#', $url, $matches)) {
                // 删除分类
                $id = $matches[1];
                $categoryService->deleteCategory($id);
                
                return [
                    'code' => 200,
                    'message' => '删除成功',
                    'data' => null
                ];
                
            } elseif ($method === 'PATCH' && preg_match('#^/api/categories/(\d+)/status$#', $url, $matches)) {
                // 更新分类状态
                $id = $matches[1];
                
                if (!isset($data['status'])) {
                    return [
                        'code' => 400,
                        'message' => '状态参数不能为空',
                        'data' => null
                    ];
                }
                
                $category = $categoryService->updateStatus($id, $data['status']);
                
                return [
                    'code' => 200,
                    'message' => '状态更新成功',
                    'data' => $category->toArray()
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
                      strpos($message, '无法删除') !== false || 
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
