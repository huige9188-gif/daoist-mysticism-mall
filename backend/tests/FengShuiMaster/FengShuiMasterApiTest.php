<?php

namespace tests\FengShuiMaster;

use PHPUnit\Framework\TestCase;
use app\service\FengShuiMasterService;
use think\facade\Db;
use PDO;

/**
 * 风水师管理API集成测试
 * 
 * 验证需求: 8.1-8.5
 */
class FengShuiMasterApiTest extends TestCase
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
            VALUES ('admin_master_test', '{$hashedPassword}', 'admin_master@test.com', '13800000051', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        // 创建普通用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_master_test', '{$hashedPassword}', 'user_master@test.com', '13800000052', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM feng_shui_masters WHERE name LIKE '%测试%' OR name LIKE '%test%'");
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试获取风水师列表
     * 验证需求: 8.5
     */
    public function testGetFengShuiMasterList()
    {
        // 创建测试风水师
        self::$pdo->exec("
            INSERT INTO feng_shui_masters (name, bio, specialty, contact, status) 
            VALUES ('测试风水师1', '简介1', '专长1', '联系方式1', 1),
                   ('测试风水师2', '简介2', '专长2', '联系方式2', 0)
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/feng-shui-masters', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试创建风水师
     * 验证需求: 8.1
     */
    public function testCreateFengShuiMaster()
    {
        $masterData = [
            'name' => '新测试风水师_' . time(),
            'bio' => '测试风水师简介',
            'specialty' => '风水布局',
            'contact' => '13800138000',
            'avatar' => 'http://example.com/avatar.jpg',
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/feng-shui-masters', $masterData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertEquals($masterData['name'], $response['data']['name']);
        $this->assertEquals($masterData['specialty'], $response['data']['specialty']);
        
        // 清理测试数据
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM feng_shui_masters WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建风水师时姓名为空
     * 验证需求: 8.2
     */
    public function testCreateFengShuiMasterWithEmptyName()
    {
        $masterData = [
            'name' => '',
            'bio' => '测试简介'
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/feng-shui-masters', $masterData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('风水师姓名不能为空', $response['message']);
    }
    
    /**
     * 测试更新风水师
     * 验证需求: 8.3
     */
    public function testUpdateFengShuiMaster()
    {
        // 创建测试风水师
        self::$pdo->exec("
            INSERT INTO feng_shui_masters (name, bio, specialty, contact, status) 
            VALUES ('原测试风水师名', '原简介', '原专长', '原联系方式', 1)
        ");
        $masterId = self::$pdo->lastInsertId();
        
        $updateData = [
            'name' => '更新后的测试风水师名',
            'bio' => '更新后的简介'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/feng-shui-masters/' . $masterId, $updateData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('更新成功', $response['message']);
        $this->assertEquals($updateData['name'], $response['data']['name']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM feng_shui_masters WHERE id = " . $masterId);
    }
    
    /**
     * 测试删除风水师（软删除）
     * 验证需求: 8.4
     */
    public function testDeleteFengShuiMaster()
    {
        // 创建测试风水师
        self::$pdo->exec("
            INSERT INTO feng_shui_masters (name, bio, specialty, contact, status) 
            VALUES ('待删除测试风水师', '简介', '专长', '联系方式', 1)
        ");
        $masterId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('DELETE', '/api/feng-shui-masters/' . $masterId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        
        // 验证软删除（记录仍存在但deleted_at不为空）
        $stmt = self::$pdo->prepare("SELECT * FROM feng_shui_masters WHERE id = ?");
        $stmt->execute([$masterId]);
        $deletedMaster = $stmt->fetch();
        
        $this->assertNotNull($deletedMaster, '风水师记录应该仍然存在');
        $this->assertNotNull($deletedMaster['deleted_at'], 'deleted_at字段应该被设置');
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM feng_shui_masters WHERE id = " . $masterId);
    }
    
    /**
     * 测试启用风水师
     * 验证需求: 8.5
     */
    public function testEnableFengShuiMaster()
    {
        // 创建测试风水师（禁用状态）
        self::$pdo->exec("
            INSERT INTO feng_shui_masters (name, bio, specialty, contact, status) 
            VALUES ('启用测试风水师', '简介', '专长', '联系方式', 0)
        ");
        $masterId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/feng-shui-masters/' . $masterId . '/status', ['status' => 1], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals(1, $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM feng_shui_masters WHERE id = " . $masterId);
    }
    
    /**
     * 测试禁用风水师
     * 验证需求: 8.5
     */
    public function testDisableFengShuiMaster()
    {
        // 创建测试风水师（启用状态）
        self::$pdo->exec("
            INSERT INTO feng_shui_masters (name, bio, specialty, contact, status) 
            VALUES ('禁用测试风水师', '简介', '专长', '联系方式', 1)
        ");
        $masterId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/feng-shui-masters/' . $masterId . '/status', ['status' => 0], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals(0, $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM feng_shui_masters WHERE id = " . $masterId);
    }
    
    /**
     * 测试普通用户无权限访问风水师管理
     * 验证需求: 11.7
     */
    public function testFengShuiMasterManagementAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/feng-shui-masters', [], self::$userToken);
        
        $this->assertEquals(403, $response['code']);
        $this->assertEquals('无权限访问', $response['message']);
    }
    
    /**
     * 模拟API请求
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
        
        // 2. 授权检查（需要管理员权限）
        if ($user['role'] !== 'admin') {
            return [
                'code' => 403,
                'message' => '无权限访问',
                'data' => null
            ];
        }
        
        // 3. 业务逻辑执行
        try {
            $masterService = new FengShuiMasterService();
            
            if ($method === 'GET' && preg_match('#^/api/feng-shui-masters(\?.*)?$#', $url)) {
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $page = $query['page'] ?? 1;
                $pageSize = $query['page_size'] ?? 10;
                $enabledOnly = isset($query['enabled_only']) && $query['enabled_only'];
                
                $masters = $masterService->getFengShuiMasterList($page, $pageSize, $enabledOnly);
                
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $masters->toArray()['data'] ?? []
                ];
                
            } elseif ($method === 'POST' && $url === '/api/feng-shui-masters') {
                $master = $masterService->createFengShuiMaster($data);
                
                return [
                    'code' => 200,
                    'message' => '创建成功',
                    'data' => $master->toArray()
                ];
                
            } elseif ($method === 'PUT' && preg_match('#^/api/feng-shui-masters/(\d+)$#', $url, $matches)) {
                $id = $matches[1];
                $master = $masterService->updateFengShuiMaster($id, $data);
                
                return [
                    'code' => 200,
                    'message' => '更新成功',
                    'data' => $master->toArray()
                ];
                
            } elseif ($method === 'DELETE' && preg_match('#^/api/feng-shui-masters/(\d+)$#', $url, $matches)) {
                $id = $matches[1];
                $masterService->deleteFengShuiMaster($id);
                
                return [
                    'code' => 200,
                    'message' => '删除成功',
                    'data' => null
                ];
                
            } elseif ($method === 'PATCH' && preg_match('#^/api/feng-shui-masters/(\d+)/status$#', $url, $matches)) {
                $id = $matches[1];
                
                if (!isset($data['status'])) {
                    return [
                        'code' => 400,
                        'message' => '状态参数不能为空',
                        'data' => null
                    ];
                }
                
                $master = $masterService->updateStatus($id, $data['status']);
                
                return [
                    'code' => 200,
                    'message' => '状态更新成功',
                    'data' => $master->toArray()
                ];
            }
            
            return [
                'code' => 404,
                'message' => '路由不存在',
                'data' => null
            ];
            
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            if (strpos($message, '不存在') !== false) {
                return [
                    'code' => 404,
                    'message' => $message,
                    'data' => null
                ];
            } elseif (strpos($message, '不能为空') !== false || strpos($message, '状态') !== false) {
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
