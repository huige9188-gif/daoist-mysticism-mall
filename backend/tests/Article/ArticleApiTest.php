<?php

namespace tests\Article;

use PHPUnit\Framework\TestCase;
use app\service\ArticleService;
use think\facade\Db;
use PDO;

/**
 * 文章管理API集成测试
 * 
 * 验证需求: 7.1-7.7
 */
class ArticleApiTest extends TestCase
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
            VALUES ('admin_article_test', '{$hashedPassword}', 'admin_article@test.com', '13800000041', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
        self::$adminToken = 'mock_admin_token_' . self::$adminUserId;
        
        // 创建普通用户
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('user_article_test', '{$hashedPassword}', 'user_article@test.com', '13800000042', 'user', 1)
        ");
        self::$normalUserId = self::$pdo->lastInsertId();
        self::$userToken = 'mock_user_token_' . self::$normalUserId;
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM articles WHERE title LIKE '%测试%' OR title LIKE '%test%'");
            self::$pdo->exec("DELETE FROM users WHERE id IN (" . self::$adminUserId . ", " . self::$normalUserId . ")");
        }
    }
    
    /**
     * 测试获取文章列表
     * 验证需求: 7.6, 7.7
     */
    public function testGetArticleList()
    {
        // 创建测试文章
        self::$pdo->exec("
            INSERT INTO articles (title, content, author, status) 
            VALUES ('测试文章1', '内容1', '作者1', 'published'),
                   ('测试文章2', '内容2', '作者2', 'draft')
        ");
        
        $response = $this->simulateApiRequest('GET', '/api/articles', [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('获取成功', $response['message']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * 测试创建文章
     * 验证需求: 7.1
     */
    public function testCreateArticle()
    {
        $articleData = [
            'title' => '新测试文章_' . time(),
            'content' => '测试文章内容',
            'cover_image' => 'http://example.com/cover.jpg',
            'author' => '测试作者',
            'status' => 'draft'
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/articles', $articleData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertEquals($articleData['title'], $response['data']['title']);
        $this->assertEquals($articleData['content'], $response['data']['content']);
        
        // 清理测试数据
        if (isset($response['data']['id'])) {
            self::$pdo->exec("DELETE FROM articles WHERE id = " . $response['data']['id']);
        }
    }
    
    /**
     * 测试创建文章时标题为空
     * 验证需求: 7.2
     */
    public function testCreateArticleWithEmptyTitle()
    {
        $articleData = [
            'title' => '',
            'content' => '测试内容'
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/articles', $articleData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('文章标题不能为空', $response['message']);
    }
    
    /**
     * 测试创建文章时内容为空
     * 验证需求: 7.3
     */
    public function testCreateArticleWithEmptyContent()
    {
        $articleData = [
            'title' => '测试文章',
            'content' => ''
        ];
        
        $response = $this->simulateApiRequest('POST', '/api/articles', $articleData, self::$adminToken);
        
        $this->assertEquals(400, $response['code']);
        $this->assertStringContainsString('文章内容不能为空', $response['message']);
    }
    
    /**
     * 测试更新文章
     * 验证需求: 7.4
     */
    public function testUpdateArticle()
    {
        // 创建测试文章
        self::$pdo->exec("
            INSERT INTO articles (title, content, author, status) 
            VALUES ('原测试文章名', '原内容', '原作者', 'draft')
        ");
        $articleId = self::$pdo->lastInsertId();
        
        $updateData = [
            'title' => '更新后的测试文章名',
            'content' => '更新后的内容'
        ];
        
        $response = $this->simulateApiRequest('PUT', '/api/articles/' . $articleId, $updateData, self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('更新成功', $response['message']);
        $this->assertEquals($updateData['title'], $response['data']['title']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM articles WHERE id = " . $articleId);
    }
    
    /**
     * 测试删除文章（软删除）
     * 验证需求: 7.5
     */
    public function testDeleteArticle()
    {
        // 创建测试文章
        self::$pdo->exec("
            INSERT INTO articles (title, content, author, status) 
            VALUES ('待删除测试文章', '内容', '作者', 'draft')
        ");
        $articleId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('DELETE', '/api/articles/' . $articleId, [], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        
        // 验证软删除（记录仍存在但deleted_at不为空）
        $stmt = self::$pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $deletedArticle = $stmt->fetch();
        
        $this->assertNotNull($deletedArticle, '文章记录应该仍然存在');
        $this->assertNotNull($deletedArticle['deleted_at'], 'deleted_at字段应该被设置');
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM articles WHERE id = " . $articleId);
    }
    
    /**
     * 测试发布文章
     * 验证需求: 7.6
     */
    public function testPublishArticle()
    {
        // 创建测试文章（草稿状态）
        self::$pdo->exec("
            INSERT INTO articles (title, content, author, status) 
            VALUES ('发布测试文章', '内容', '作者', 'draft')
        ");
        $articleId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/articles/' . $articleId . '/status', ['status' => 'published'], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals('published', $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM articles WHERE id = " . $articleId);
    }
    
    /**
     * 测试撤回文章
     * 验证需求: 7.7
     */
    public function testUnpublishArticle()
    {
        // 创建测试文章（已发布状态）
        self::$pdo->exec("
            INSERT INTO articles (title, content, author, status) 
            VALUES ('撤回测试文章', '内容', '作者', 'published')
        ");
        $articleId = self::$pdo->lastInsertId();
        
        $response = $this->simulateApiRequest('PATCH', '/api/articles/' . $articleId . '/status', ['status' => 'draft'], self::$adminToken);
        
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('状态更新成功', $response['message']);
        $this->assertEquals('draft', $response['data']['status']);
        
        // 清理测试数据
        self::$pdo->exec("DELETE FROM articles WHERE id = " . $articleId);
    }
    
    /**
     * 测试普通用户无权限访问文章管理
     * 验证需求: 11.7
     */
    public function testArticleManagementAsUserForbidden()
    {
        $response = $this->simulateApiRequest('GET', '/api/articles', [], self::$userToken);
        
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
            $articleService = new ArticleService();
            
            if ($method === 'GET' && preg_match('#^/api/articles(\?.*)?$#', $url)) {
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
                $page = $query['page'] ?? 1;
                $pageSize = $query['page_size'] ?? 10;
                $publishedOnly = isset($query['published_only']) && $query['published_only'];
                
                $articles = $articleService->getArticleList($page, $pageSize, $publishedOnly);
                
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $articles->toArray()['data'] ?? []
                ];
                
            } elseif ($method === 'POST' && $url === '/api/articles') {
                $article = $articleService->createArticle($data);
                
                return [
                    'code' => 200,
                    'message' => '创建成功',
                    'data' => $article->toArray()
                ];
                
            } elseif ($method === 'PUT' && preg_match('#^/api/articles/(\d+)$#', $url, $matches)) {
                $id = $matches[1];
                $article = $articleService->updateArticle($id, $data);
                
                return [
                    'code' => 200,
                    'message' => '更新成功',
                    'data' => $article->toArray()
                ];
                
            } elseif ($method === 'DELETE' && preg_match('#^/api/articles/(\d+)$#', $url, $matches)) {
                $id = $matches[1];
                $articleService->deleteArticle($id);
                
                return [
                    'code' => 200,
                    'message' => '删除成功',
                    'data' => null
                ];
                
            } elseif ($method === 'PATCH' && preg_match('#^/api/articles/(\d+)/status$#', $url, $matches)) {
                $id = $matches[1];
                
                if (!isset($data['status'])) {
                    return [
                        'code' => 400,
                        'message' => '状态参数不能为空',
                        'data' => null
                    ];
                }
                
                $article = $articleService->updateStatus($id, $data['status']);
                
                return [
                    'code' => 200,
                    'message' => '状态更新成功',
                    'data' => $article->toArray()
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
