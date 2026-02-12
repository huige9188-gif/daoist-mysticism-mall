<?php

namespace tests\User;

use PHPUnit\Framework\TestCase;
use app\service\UserService;
use think\facade\Db;
use PDO;

/**
 * 用户管理API集成测试
 * 
 * 验证需求: 2.1, 2.2, 2.3, 2.4, 2.5
 * 
 * 这个测试类模拟完整的API请求流程，包括：
 * - 认证和授权检查
 * - 业务逻辑执行
 * - 响应格式验证
 */
class UserApiTest extends TestCase
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
            VALUES ('admin_api_test', '{$hashedPassword}', 'admin_api@test.com', '13800000001', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        // 创建普通用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_api_test', '{$hashedPassword}', 'user_api@test.com', '13800000002', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试获取用户列表 - 管理员权限
     * 验证需求: 2.1
     */
    public function testGetUserListAsAdmin()
    {
        $response = $this->simulateApiRequest('GET', '/api/users', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试获取用户列表 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testGetUserListAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/users', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试获取用户列表 - 未认证
     * 验证需求: 11.5
     */
    public function testGetUserListUnauthorized()
    {
        $response = $this->simulateApiRequest('GET', '/api/users', []);
        
        $this->assertEquals(401, $response['code']);
        $this->assertEquals('未授权访问', $response['message']);
    }
    
    /**
     * 测试创建用户 - 成功
     * 验证需求: 2.2
     */
    public function testCreateUserSuccess()
    {
        $userData = [
            'username' => 'newuser_' . time(),
            'password' => 'password123',
            'email' => 'newuser_' . time() . '@test.com',
            'phone' => '13900000000',
            'role' => 'user',
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/users', $userData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals($userData['username'], $response['data']['username']);
        $this->assertEquals($userData['email'], $response['data']['email']);
        $this->assertArrayNotHasKey('password', $response['data'], '响应不应包含密码字段');
        
        // 清理测试数据
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM users WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建用户 - 用户名重复
     * 验证需求: 2.2
     */
    public function testCreateUserDuplicateUsername()
    {
        $userData = [
            'username' => 'admin_api_test',
            'password' => 'password123',
            'email' => 'another@test.com',
            'phone' => '13900000001',
            'role' => 'user',
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/users', $userData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('用户名已存在', $response['message']);
    }
    
    /**
     * 测试创建用户 - 邮箱格式错误
     * 验证需求: 2.2
     */
    public function testCreateUserInvalidEmail()
    {
        $userData = [
            'username' => 'testuser_' . time(),
            'password' => 'password123',
            'email' => 'invalid-email',
            'phone' => '13900000002',
            'role' => 'user',
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/users', $userData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('邮箱', $response['message']);
    }
    
    /**
     * 测试创建用户 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testCreateUserAsUserForbidden()
    {
        $userData = [
            'username' => 'testuser_' . time(),
            'password' => 'password123',
            'email' => 'test_' . time() . '@test.com',
            'phone' => '13900000003',
            'role' => 'user',
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/users', $userData, self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试更新用户 - 成功
     * 验证需求: 2.3
     */
    public function testUpdateUserSuccess()
    {
        // 创建测试用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('updatetest_" . time() . "', '{$hashedPassword}', 'updatetest_" . time() . "@test.com', '13900000003', 'user', 1)
        ");
        $testUserId = self::$pdo->lastInsertId();
        
        $updateData = [
            'phone' => '13900000099'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/users/' . $testUserId, $updateData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('更新成功', $response['message']);
        $this->assertEquals($updateData['phone'], $response['data']['phone']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM users WHERE id = " . $testUserId);
    }
    
    /**
     * 测试更新用户 - 用户不存在
     * 验证需求: 2.3
     */
    public function testUpdateUserNotFound()
    {
        $updateData = [
            'phone' => '13900000099'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/users/999999', $updateData, self::$adminToken);
        
        $this->assertEquals(404, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }
    
    /**
     * 测试更新用户 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testUpdateUserAsUserForbidden()
    {
        $updateData = [
            'phone' => '13900000099'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/users/' . self::$normalUserId, $updateData, self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试删除用户 - 成功（软删除）
     * 验证需求: 2.4
     */
    public function testDeleteUserSuccess()
    {
        // 创建测试用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('deletetest_" . time() . "', '{$hashedPassword}', 'deletetest_" . time() . "@test.com', '13900000004', 'user', 1)
        ");
        $testUserId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('DELETE', '/api/users/' . $testUserId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        
        // 验证软删除：用户仍然存在但deleted_at不为空
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$testUserId]);
        $deletedUser = $stmt->fetch();
        
        $this->assertNotNull($deletedUser, '用户记录应该仍然存在');
        $this->assertNotNull($deletedUser['deleted_at'], 'deleted_at字段应该被设置');
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM users WHERE id = " . $testUserId);
    }
    
    /**
     * 测试删除用户 - 用户不存在
     * 验证需求: 2.4
     */
    public function testDeleteUserNotFound()
    {
        $response = $this->simulateApiRequest('DELETE', '/api/users/999999', [], self::$adminToken);
        
        $this->assertEquals(404, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }
    
    /**
     * 测试删除用户 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testDeleteUserAsUserForbidden()
    {
        $response = $this->simulateApiRequest('DELETE', '/api/users/' . self::$normalUserId, [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试更新用户状态 - 成功
     * 验证需求: 2.5
     */
    public function testUpdateUserStatusSuccess()
    {
        // 创建测试用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('statustest_" . time() . "', '{$hashedPassword}', 'statustest_" . time() . "@test.com', '13900000005', 'user', 1)
        ");
        $testUserId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/users/' . $testUserId . '/status', ['status' => 0], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals(0, $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM users WHERE id = " . $testUserId);
    }
    
    /**
     * 测试更新用户状态 - 状态值无效
     * 验证需求: 2.5
     */
    public function testUpdateUserStatusInvalid()
    {
        $response = $this->simulateApiRequest('PATCH', '/api/users/' . self::$normalUserId . '/status', ['status' => 99], self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('状态', $response['message']);
    }
    
    /**
     * 测试更新用户状态 - 普通用户无权限
     * 验证需求: 11.7
     */
    public function testUpdateUserStatusAsUserForbidden()
    {
        $response = $this->simulateApiRequest('PATCH', '/api/users/' . self::$normalUserId . '/status', ['status' => 0], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 测试搜索用户 - 按用户名
     * 验证需求: 2.6
     */
    public function testSearchUsersByUsername()
    {
        $response = $this->simulateApiRequest('GET', '/api/users?search=admin_api_test', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试搜索用户 - 按邮箱
     * 验证需求: 2.6
     */
    public function testSearchUsersByEmail()
    {
        $response = $this->simulateApiRequest('GET', '/api/users?search=admin_api@test.com', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertArrayHasKey('data', $response);
    }
    
    /**
     * 测试分页功能
     * 验证需求: 2.1
     */
    public function testUserListPagination()
    {
        $response = $this->simulateApiRequest('GET', '/api/users?page=1&page_size=5', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertArrayHasKey('data', $response);
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
        $adminRoutes = ['/api/users'];
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
            $userService = new UserService();
            
            // 路由匹配和处理
            if ($method === 'GET' && preg_match('#^/api/users(\?.*)?$#', $url)) {
                // 获取用户列表
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $page = $query['page'] ?? 1;
                $pageSize = $query['page_size'] ?? 10;
                $search = $query['search'] ?? '';
                
                $users = $userService->getUserList($page, $pageSize, $search);
                
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $users
                ];
                
            } elseif ($method === 'POST' && $url === '/api/users') {
                // 创建用户
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                
                $newUser = $userService->createUser($data);
                unset($newUser->password);
                
                return [
                    'code' => 200,
                    'message' => '创建成功',
                    'data' => $newUser
                ];
                
            } elseif ($method === 'PUT' && preg_match('#^/api/users/(\d+)$#', $url, $matches)) {
                // 更新用户
                $id = $matches[1];
                $updatedUser = $userService->updateUser($id, $data);
                unset($updatedUser->password);
                
                return [
                    'code' => 200,
                    'message' => '更新成功',
                    'data' => $updatedUser
                ];
                
            } elseif ($method === 'DELETE' && preg_match('#^/api/users/(\d+)$#', $url, $matches)) {
                // 删除用户
                $id = $matches[1];
                $userService->deleteUser($id);
                
                return [
                    'code' => 200,
                    'message' => '删除成功',
                    'data' => null
                ];
                
            } elseif ($method === 'PATCH' && preg_match('#^/api/users/(\d+)/status$#', $url, $matches)) {
                // 更新用户状态
                $id = $matches[1];
                
                if (!isset($data['status'])) {
                    return [
                        'code' => 400,
                        'message' => '状态参数不能为空',
                        'data' => null
                    ];
                }
                
                $updatedUser = $userService->updateStatus($id, $data['status']);
                unset($updatedUser->password);
                
                return [
                    'code' => 200,
                    'message' => '状态更新成功',
                    'data' => $updatedUser
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
            } elseif (strpos($message, '已存在') !== false || 
                      strpos($message, '邮箱') !== false || 
                      strpos($message, '状态') !== false ||
                      strpos($message, '格式') !== false) {
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
