<?php

namespace Tests\Article;

use PHPUnit\Framework\TestCase;
use app\model\Article;
use app\service\ArticleService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 文章服务测试
 * 
 * 验证需求: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7
 */
class ArticleServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private ArticleService $service;
    
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
        $this->service = new ArticleService();
        
        // 清空文章表
        self::$pdo->exec("TRUNCATE TABLE articles");
    }
    
    /**
     * 测试创建文章成功
     * 验证需求: 7.1
     */
    public function testCreateArticleSuccess(): void
    {
        $data = [
            'title' => '道家养生之道',
            'content' => '道家养生注重天人合一，顺应自然规律...',
            'cover_image' => 'https://example.com/images/yangsheng.jpg',
            'author' => '张三丰',
            'status' => Article::STATUS_PUBLISHED
        ];
        
        $article = $this->service->createArticle($data);
        
        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('道家养生之道', $article->title);
        $this->assertEquals('道家养生注重天人合一，顺应自然规律...', $article->content);
        $this->assertEquals('张三丰', $article->author);
        $this->assertEquals(Article::STATUS_PUBLISHED, $article->status);
    }
    
    /**
     * 测试创建文章时标题为空
     * 验证需求: 7.2
     */
    public function testCreateArticleWithEmptyTitle(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文章标题不能为空');
        
        $this->service->createArticle([
            'title' => '',
            'content' => '这是文章内容'
        ]);
    }
    
    /**
     * 测试创建文章时标题为空白字符
     * 验证需求: 7.2
     */
    public function testCreateArticleWithWhitespaceTitle(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文章标题不能为空');
        
        $this->service->createArticle([
            'title' => '   ',
            'content' => '这是文章内容'
        ]);
    }
    
    /**
     * 测试创建文章时内容为空
     * 验证需求: 7.3
     */
    public function testCreateArticleWithEmptyContent(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文章内容不能为空');
        
        $this->service->createArticle([
            'title' => '测试文章',
            'content' => ''
        ]);
    }
    
    /**
     * 测试创建文章时内容为空白字符
     * 验证需求: 7.3
     */
    public function testCreateArticleWithWhitespaceContent(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文章内容不能为空');
        
        $this->service->createArticle([
            'title' => '测试文章',
            'content' => '   '
        ]);
    }
    
    /**
     * 测试创建文章时使用默认状态（草稿）
     * 验证需求: 7.1
     */
    public function testCreateArticleWithDefaultStatus(): void
    {
        $article = $this->service->createArticle([
            'title' => '太极拳的哲学思想',
            'content' => '太极拳蕴含着深刻的道家哲学思想...'
        ]);
        
        $this->assertEquals(Article::STATUS_DRAFT, $article->status);
    }
    
    /**
     * 测试更新文章成功
     * 验证需求: 7.4
     */
    public function testUpdateArticleSuccess(): void
    {
        // 创建文章
        $article = $this->service->createArticle([
            'title' => '八卦掌入门',
            'content' => '八卦掌是道家内家拳的代表...',
            'author' => '董海川'
        ]);
        
        // 更新文章
        $updatedArticle = $this->service->updateArticle($article->id, [
            'title' => '八卦掌高级技法',
            'content' => '八卦掌高级技法包括走转、换掌等...',
            'author' => '董海川传人'
        ]);
        
        $this->assertEquals('八卦掌高级技法', $updatedArticle->title);
        $this->assertEquals('八卦掌高级技法包括走转、换掌等...', $updatedArticle->content);
        $this->assertEquals('董海川传人', $updatedArticle->author);
    }
    
    /**
     * 测试更新不存在的文章
     * 验证需求: 7.4
     */
    public function testUpdateNonExistentArticle(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('文章不存在');
        
        $this->service->updateArticle(99999, [
            'title' => '测试文章'
        ]);
    }
    
    /**
     * 测试更新文章时标题为空
     * 验证需求: 7.2
     */
    public function testUpdateArticleWithEmptyTitle(): void
    {
        // 创建文章
        $article = $this->service->createArticle([
            'title' => '形意拳概述',
            'content' => '形意拳是中国传统武术之一...'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文章标题不能为空');
        
        $this->service->updateArticle($article->id, [
            'title' => ''
        ]);
    }
    
    /**
     * 测试更新文章时内容为空
     * 验证需求: 7.3
     */
    public function testUpdateArticleWithEmptyContent(): void
    {
        // 创建文章
        $article = $this->service->createArticle([
            'title' => '易筋经详解',
            'content' => '易筋经是道家养生功法...'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文章内容不能为空');
        
        $this->service->updateArticle($article->id, [
            'content' => ''
        ]);
    }
    
    /**
     * 测试删除文章成功
     * 验证需求: 7.5
     */
    public function testDeleteArticleSuccess(): void
    {
        // 创建文章
        $article = $this->service->createArticle([
            'title' => '站桩功要领',
            'content' => '站桩功是道家修炼的基础功法...'
        ]);
        
        // 删除文章
        $result = $this->service->deleteArticle($article->id);
        $this->assertTrue($result);
        
        // 验证文章仍然存在于数据库中（软删除）
        $stmt = self::$pdo->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $article->id]);
        $deletedArticle = $stmt->fetch();
        
        $this->assertNotNull($deletedArticle);
        $this->assertNotNull($deletedArticle['deleted_at']);
    }
    
    /**
     * 测试删除不存在的文章
     * 验证需求: 7.5
     */
    public function testDeleteNonExistentArticle(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('文章不存在');
        
        $this->service->deleteArticle(99999);
    }
    
    /**
     * 测试发布文章
     * 验证需求: 7.6
     */
    public function testPublishArticle(): void
    {
        // 创建草稿文章
        $article = $this->service->createArticle([
            'title' => '打坐入门指南',
            'content' => '打坐是道家修炼的重要方法...'
        ]);
        
        $this->assertEquals(Article::STATUS_DRAFT, $article->status);
        
        // 发布文章
        $publishedArticle = $this->service->publishArticle($article->id);
        
        $this->assertEquals(Article::STATUS_PUBLISHED, $publishedArticle->status);
    }
    
    /**
     * 测试撤回文章（设置为草稿）
     * 验证需求: 7.7
     */
    public function testUnpublishArticle(): void
    {
        // 创建已发布文章
        $article = $this->service->createArticle([
            'title' => '内丹修炼法',
            'content' => '内丹修炼是道家高级修炼方法...',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        $this->assertEquals(Article::STATUS_PUBLISHED, $article->status);
        
        // 撤回文章
        $unpublishedArticle = $this->service->unpublishArticle($article->id);
        
        $this->assertEquals(Article::STATUS_DRAFT, $unpublishedArticle->status);
    }
    
    /**
     * 测试更新文章状态为已发布
     * 验证需求: 7.6
     */
    public function testUpdateArticleStatusToPublished(): void
    {
        // 创建草稿文章
        $article = $this->service->createArticle([
            'title' => '五禽戏养生法',
            'content' => '五禽戏是华佗创编的养生功法...'
        ]);
        
        // 更新状态为已发布
        $updatedArticle = $this->service->updateStatus($article->id, Article::STATUS_PUBLISHED);
        
        $this->assertEquals(Article::STATUS_PUBLISHED, $updatedArticle->status);
    }
    
    /**
     * 测试更新文章状态为草稿
     * 验证需求: 7.7
     */
    public function testUpdateArticleStatusToDraft(): void
    {
        // 创建已发布文章
        $article = $this->service->createArticle([
            'title' => '六字诀养生',
            'content' => '六字诀是道家吐纳养生法...',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        // 更新状态为草稿
        $updatedArticle = $this->service->updateStatus($article->id, Article::STATUS_DRAFT);
        
        $this->assertEquals(Article::STATUS_DRAFT, $updatedArticle->status);
    }
    
    /**
     * 测试更新文章状态时状态值无效
     * 验证需求: 7.6, 7.7
     */
    public function testUpdateArticleStatusWithInvalidValue(): void
    {
        // 创建文章
        $article = $this->service->createArticle([
            'title' => '测试文章',
            'content' => '测试内容'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus($article->id, 'invalid_status');
    }
    
    /**
     * 测试获取文章列表
     */
    public function testGetArticleList(): void
    {
        // 创建多篇文章
        $this->service->createArticle([
            'title' => '文章A',
            'content' => '内容A',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        $this->service->createArticle([
            'title' => '文章B',
            'content' => '内容B',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        $this->service->createArticle([
            'title' => '文章C',
            'content' => '内容C',
            'status' => Article::STATUS_DRAFT
        ]);
        
        // 获取文章列表
        $result = $this->service->getArticleList(1, 10);
        
        $this->assertCount(3, $result->items());
    }
    
    /**
     * 测试只获取已发布的文章
     * 验证需求: 7.6
     */
    public function testGetPublishedArticlesOnly(): void
    {
        // 创建已发布和草稿文章
        $this->service->createArticle([
            'title' => '已发布文章1',
            'content' => '内容1',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        $this->service->createArticle([
            'title' => '草稿文章',
            'content' => '草稿内容',
            'status' => Article::STATUS_DRAFT
        ]);
        
        $this->service->createArticle([
            'title' => '已发布文章2',
            'content' => '内容2',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        // 只获取已发布的文章
        $result = $this->service->getArticleList(1, 10, true);
        
        $this->assertCount(2, $result->items());
        foreach ($result->items() as $article) {
            $this->assertEquals(Article::STATUS_PUBLISHED, $article->status);
        }
    }
    
    /**
     * 测试根据ID获取文章
     */
    public function testGetArticleById(): void
    {
        // 创建文章
        $article = $this->service->createArticle([
            'title' => '测试文章',
            'content' => '测试内容'
        ]);
        
        // 根据ID获取文章
        $foundArticle = $this->service->getArticleById($article->id);
        
        $this->assertNotNull($foundArticle);
        $this->assertEquals('测试文章', $foundArticle->title);
    }
    
    /**
     * 测试获取不存在的文章
     */
    public function testGetNonExistentArticleById(): void
    {
        $article = $this->service->getArticleById(99999);
        
        $this->assertNull($article);
    }
    
    /**
     * 测试文章模型的isPublished方法
     */
    public function testArticleIsPublished(): void
    {
        // 创建已发布文章
        $publishedArticle = $this->service->createArticle([
            'title' => '已发布文章',
            'content' => '内容',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        $this->assertTrue($publishedArticle->isPublished());
        
        // 创建草稿文章
        $draftArticle = $this->service->createArticle([
            'title' => '草稿文章',
            'content' => '内容'
        ]);
        
        $this->assertFalse($draftArticle->isPublished());
    }
    
    /**
     * 测试文章模型的isDraft方法
     */
    public function testArticleIsDraft(): void
    {
        // 创建草稿文章
        $draftArticle = $this->service->createArticle([
            'title' => '草稿文章',
            'content' => '内容'
        ]);
        
        $this->assertTrue($draftArticle->isDraft());
        
        // 创建已发布文章
        $publishedArticle = $this->service->createArticle([
            'title' => '已发布文章',
            'content' => '内容',
            'status' => Article::STATUS_PUBLISHED
        ]);
        
        $this->assertFalse($publishedArticle->isDraft());
    }
}
