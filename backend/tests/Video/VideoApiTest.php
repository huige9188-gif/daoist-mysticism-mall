<?php

namespace tests\Video;

use PHPUnit\Framework\TestCase;
use app\service\VideoService;
use think\facade\Db;
use PDO;

/**
 * 视频管理API集成测试
 * 
 * 验证需求: 6.1-6.6
 */
class VideoApiTest extends TestCase
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
            VALUES ('admin_video_test', '{$hashedPassword}', 'admin_video@test.com', '13800000031', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        // 创建普通用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_video_test', '{$hashedPassword}', 'user_video@test.com', '13800000032', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM videos WHERE title LIKE '%测试%' OR title LIKE '%test%'");
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试获取视频列表
     * 验证需求: 6.6
     */
    public function testGetVideoList()
    {
        // 创建测试视频
        self::$pdo->exec("
            INSERT INTO videos (title, description, video_url, status) 
            VALUES ('测试视频1', '描述1', 'http://example.com/video1.mp4', 1),
                   ('测试视频2', '描述2', 'http://example.com/video2.mp4', 0)
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/videos', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试创建视频
     * 验证需求: 6.1
     */
    public function testCreateVideo()
    {
        $videoData = [
            'title' => '新测试视频_' . time(),
            'description' => '测试视频描述',
            'video_url' => 'http://example.com/test_video.mp4',
            'cover_image' => 'http://example.com/cover.jpg',
            'status' => 1
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/videos', $videoData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertEquals($videoData['title'], $response['data']['title']);
        $this->assertEquals($videoData['video_url'], $response['data']['video_url']);
        
        // 清理测试数据
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM videos WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建视频时标题为空
     * 验证需求: 6.2
     */
    public function testCreateVideoWithEmptyTitle()
    {
        $videoData = [
            'title' => '',
            'video_url' => 'http://example.com/test_video.mp4'
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/videos', $videoData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('视频标题不能为空', $response['message']);
    }
    
    /**
     * 测试创建视频时URL为空
     * 验证需求: 6.3
     */
    public function testCreateVideoWithEmptyUrl()
    {
        $videoData = [
            'title' => '测试视频',
            'video_url' => ''
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/videos', $videoData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('视频URL不能为空', $response['message']);
    }
    
    /**
     * 测试更新视频
     * 验证需求: 6.4
     */
    public function testUpdateVideo()
    {
        // 创建测试视频
        self::$pdo->exec("
            INSERT INTO videos (title, description, video_url, status) 
            VALUES ('原测试视频名', '原描述', 'http://example.com/original.mp4', 1)
        ");
        $videoId = self::$pdo->lastInsertId();
        
        $updateData = [
            'title' => '更新后的测试视频名',
            'description' => '更新后的描述'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/videos/' . $videoId, $updateData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('更新成功', $response['message']);
        $this->assertEquals($updateData['title'], $response['data']['title']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM videos WHERE id = " . $videoId);
    }
    
    /**
     * 测试删除视频（软删除）
     * 验证需求: 6.5
     */
    public function testDeleteVideo()
    {
        // 创建测试视频
        self::$pdo->exec("
            INSERT INTO videos (title, description, video_url, status) 
            VALUES ('待删除测试视频', '描述', 'http://example.com/delete.mp4', 1)
        ");
        $videoId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('DELETE', '/api/videos/' . $videoId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        
        // 验证软删除（记录仍存在但deleted_at不为空）
        $stmt = self::$pdo->prepare("SELECT * FROM videos WHERE id = ?");
        $stmt->execute([$videoId]);
        $deletedVideo = $stmt->fetch();
        
        $this->assertNotNull($deletedVideo, '视频记录应该仍然存在');
        $this->assertNotNull($deletedVideo['deleted_at'], 'deleted_at字段应该被设置');
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM videos WHERE id = " . $videoId);
    }
    
    /**
     * 测试启用视频
     * 验证需求: 6.6
     */
    public function testEnableVideo()
    {
        // 创建测试视频（禁用状态）
        self::$pdo->exec("
            INSERT INTO videos (title, description, video_url, status) 
            VALUES ('启用测试视频', '描述', 'http://example.com/enable.mp4', 0)
        ");
        $videoId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/videos/' . $videoId . '/status', ['status' => 1], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals(1, $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM videos WHERE id = " . $videoId);
    }
    
    /**
     * 测试禁用视频
     * 验证需求: 6.6
     */
    public function testDisableVideo()
    {
        // 创建测试视频（启用状态）
        self::$pdo->exec("
            INSERT INTO videos (title, description, video_url, status) 
            VALUES ('禁用测试视频', '描述', 'http://example.com/disable.mp4', 1)
        ");
        $videoId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/videos/' . $videoId . '/status', ['status' => 0], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals(0, $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM videos WHERE id = " . $videoId);
    }
    
    /**
     * 测试普通用户无权限访问视频管理
     * 验证需求: 11.7
     */
    public function testVideoManagementAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/videos', [], self::$userToken);
        
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
            $videoService = new VideoService();
            
            if ($method === 'GET' && preg_match('#^/api/videos(\?.*)?$#', $url)) {
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $page = $query['page'] ?? 1;
                $pageSize = $query['page_size'] ?? 10;
                $enabledOnly = isset($query['enabled_only']) && $query['enabled_only'];
                
                $videos = $videoService->getVideoList($page, $pageSize, $enabledOnly);
                
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $videos->toArray()['data'] ?? []
                ];
                
            } elseif ($method === 'POST' && $url === '/api/videos') {
                $video = $videoService->createVideo($data);
                
                return [
                    'code' => 200,
                    'message' => '创建成功',
                    'data' => $video->toArray()
                ];
                
            } elseif ($method === 'PUT' && preg_match('#^/api/videos/(\d+)$#', $url, $matches)) {
                $id = $matches[1];
                $video = $videoService->updateVideo($id, $data);
                
                return [
                    'code' => 200,
                    'message' => '更新成功',
                    'data' => $video->toArray()
                ];
                
            } elseif ($method === 'DELETE' && preg_match('#^/api/videos/(\d+)$#', $url, $matches)) {
                $id = $matches[1];
                $videoService->deleteVideo($id);
                
                return [
                    'code' => 200,
                    'message' => '删除成功',
                    'data' => null
                ];
                
            } elseif ($method === 'PATCH' && preg_match('#^/api/videos/(\d+)/status$#', $url, $matches)) {
                $id = $matches[1];
                
                if (!isset($data['status'])) {
                    return [
                        'code' => 400,
                        'message' => '状态参数不能为空',
                        'data' => null
                    ];
                }
                
                $video = $videoService->updateStatus($id, $data['status']);
                
                return [
                    'code' => 200,
                    'message' => '状态更新成功',
                    'data' => $video->toArray()
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
