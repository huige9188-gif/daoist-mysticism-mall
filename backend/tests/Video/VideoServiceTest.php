<?php

namespace Tests\Video;

use PHPUnit\Framework\TestCase;
use app\model\Video;
use app\service\VideoService;
use think\exception\ValidateException;
use think\facade\Db;
use PDO;

/**
 * 视频服务测试
 * 
 * 验证需求: 6.1, 6.4, 6.5, 6.6
 */
class VideoServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private VideoService $service;
    
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
        $this->service = new VideoService();
        
        // 清空视频表
        self::$pdo->exec("TRUNCATE TABLE videos");
    }
    
    /**
     * 测试创建视频成功
     * 验证需求: 6.1
     */
    public function testCreateVideoSuccess(): void
    {
        $data = [
            'title' => '道家养生功法教学',
            'description' => '详细讲解道家养生功法的要领和注意事项',
            'video_url' => 'https://example.com/videos/yangsheng.mp4',
            'cover_image' => 'https://example.com/images/yangsheng-cover.jpg',
            'status' => 1
        ];
        
        $video = $this->service->createVideo($data);
        
        $this->assertInstanceOf(Video::class, $video);
        $this->assertEquals('道家养生功法教学', $video->title);
        $this->assertEquals('https://example.com/videos/yangsheng.mp4', $video->video_url);
        $this->assertEquals(1, $video->status);
    }
    
    /**
     * 测试创建视频时标题为空
     * 验证需求: 6.2
     */
    public function testCreateVideoWithEmptyTitle(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('视频标题不能为空');
        
        $this->service->createVideo([
            'title' => '',
            'video_url' => 'https://example.com/videos/test.mp4'
        ]);
    }
    
    /**
     * 测试创建视频时标题为空白字符
     * 验证需求: 6.2
     */
    public function testCreateVideoWithWhitespaceTitle(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('视频标题不能为空');
        
        $this->service->createVideo([
            'title' => '   ',
            'video_url' => 'https://example.com/videos/test.mp4'
        ]);
    }
    
    /**
     * 测试创建视频时视频URL为空
     * 验证需求: 6.3
     */
    public function testCreateVideoWithEmptyUrl(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('视频URL不能为空');
        
        $this->service->createVideo([
            'title' => '测试视频',
            'video_url' => ''
        ]);
    }
    
    /**
     * 测试创建视频时视频URL为空白字符
     * 验证需求: 6.3
     */
    public function testCreateVideoWithWhitespaceUrl(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('视频URL不能为空');
        
        $this->service->createVideo([
            'title' => '测试视频',
            'video_url' => '   '
        ]);
    }
    
    /**
     * 测试创建视频时使用默认状态
     * 验证需求: 6.1
     */
    public function testCreateVideoWithDefaultStatus(): void
    {
        $video = $this->service->createVideo([
            'title' => '太极拳教学',
            'video_url' => 'https://example.com/videos/taiji.mp4'
        ]);
        
        $this->assertEquals(1, $video->status);
    }
    
    /**
     * 测试更新视频成功
     * 验证需求: 6.4
     */
    public function testUpdateVideoSuccess(): void
    {
        // 创建视频
        $video = $this->service->createVideo([
            'title' => '八段锦教学',
            'video_url' => 'https://example.com/videos/baduanjin.mp4',
            'description' => '初级教学'
        ]);
        
        // 更新视频
        $updatedVideo = $this->service->updateVideo($video->id, [
            'title' => '八段锦高级教学',
            'description' => '高级教学，适合有基础的学员'
        ]);
        
        $this->assertEquals('八段锦高级教学', $updatedVideo->title);
        $this->assertEquals('高级教学，适合有基础的学员', $updatedVideo->description);
    }
    
    /**
     * 测试更新不存在的视频
     * 验证需求: 6.4
     */
    public function testUpdateNonExistentVideo(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('视频不存在');
        
        $this->service->updateVideo(99999, [
            'title' => '测试视频'
        ]);
    }
    
    /**
     * 测试更新视频时标题为空
     * 验证需求: 6.2
     */
    public function testUpdateVideoWithEmptyTitle(): void
    {
        // 创建视频
        $video = $this->service->createVideo([
            'title' => '五禽戏教学',
            'video_url' => 'https://example.com/videos/wuqinxi.mp4'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('视频标题不能为空');
        
        $this->service->updateVideo($video->id, [
            'title' => ''
        ]);
    }
    
    /**
     * 测试更新视频时视频URL为空
     * 验证需求: 6.3
     */
    public function testUpdateVideoWithEmptyUrl(): void
    {
        // 创建视频
        $video = $this->service->createVideo([
            'title' => '易筋经教学',
            'video_url' => 'https://example.com/videos/yijinjing.mp4'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('视频URL不能为空');
        
        $this->service->updateVideo($video->id, [
            'video_url' => ''
        ]);
    }
    
    /**
     * 测试删除视频成功
     * 验证需求: 6.5
     */
    public function testDeleteVideoSuccess(): void
    {
        // 创建视频
        $video = $this->service->createVideo([
            'title' => '站桩功教学',
            'video_url' => 'https://example.com/videos/zhanzhuang.mp4'
        ]);
        
        // 删除视频
        $result = $this->service->deleteVideo($video->id);
        $this->assertTrue($result);
        
        // 验证视频仍然存在于数据库中（软删除）
        $stmt = self::$pdo->prepare("SELECT * FROM videos WHERE id = :id");
        $stmt->execute(['id' => $video->id]);
        $deletedVideo = $stmt->fetch();
        
        $this->assertNotNull($deletedVideo);
        $this->assertNotNull($deletedVideo['deleted_at']);
    }
    
    /**
     * 测试删除不存在的视频
     * 验证需求: 6.5
     */
    public function testDeleteNonExistentVideo(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('视频不存在');
        
        $this->service->deleteVideo(99999);
    }
    
    /**
     * 测试更新视频状态为启用
     * 验证需求: 6.6
     */
    public function testUpdateVideoStatusToEnabled(): void
    {
        // 创建视频（禁用）
        $video = $this->service->createVideo([
            'title' => '打坐入门',
            'video_url' => 'https://example.com/videos/dazuo.mp4',
            'status' => 0
        ]);
        
        // 启用视频
        $updatedVideo = $this->service->updateStatus($video->id, 1);
        
        $this->assertEquals(1, $updatedVideo->status);
    }
    
    /**
     * 测试更新视频状态为禁用
     * 验证需求: 6.6
     */
    public function testUpdateVideoStatusToDisabled(): void
    {
        // 创建视频（启用）
        $video = $this->service->createVideo([
            'title' => '内丹修炼',
            'video_url' => 'https://example.com/videos/neidan.mp4',
            'status' => 1
        ]);
        
        // 禁用视频
        $updatedVideo = $this->service->updateStatus($video->id, 0);
        
        $this->assertEquals(0, $updatedVideo->status);
    }
    
    /**
     * 测试更新视频状态时状态值无效
     * 验证需求: 6.6
     */
    public function testUpdateVideoStatusWithInvalidValue(): void
    {
        // 创建视频
        $video = $this->service->createVideo([
            'title' => '测试视频',
            'video_url' => 'https://example.com/videos/test.mp4'
        ]);
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('状态值无效');
        
        $this->service->updateStatus($video->id, 2);
    }
    
    /**
     * 测试获取视频列表
     */
    public function testGetVideoList(): void
    {
        // 创建多个视频
        $this->service->createVideo([
            'title' => '视频A',
            'video_url' => 'https://example.com/videos/a.mp4',
            'status' => 1
        ]);
        
        $this->service->createVideo([
            'title' => '视频B',
            'video_url' => 'https://example.com/videos/b.mp4',
            'status' => 1
        ]);
        
        $this->service->createVideo([
            'title' => '视频C',
            'video_url' => 'https://example.com/videos/c.mp4',
            'status' => 0
        ]);
        
        // 获取视频列表
        $result = $this->service->getVideoList(1, 10);
        
        $this->assertCount(3, $result->items());
    }
    
    /**
     * 测试只获取启用的视频
     * 验证需求: 6.6
     */
    public function testGetEnabledVideosOnly(): void
    {
        // 创建启用和禁用的视频
        $this->service->createVideo([
            'title' => '启用视频1',
            'video_url' => 'https://example.com/videos/enabled1.mp4',
            'status' => 1
        ]);
        
        $this->service->createVideo([
            'title' => '禁用视频',
            'video_url' => 'https://example.com/videos/disabled.mp4',
            'status' => 0
        ]);
        
        $this->service->createVideo([
            'title' => '启用视频2',
            'video_url' => 'https://example.com/videos/enabled2.mp4',
            'status' => 1
        ]);
        
        // 只获取启用的视频
        $result = $this->service->getVideoList(1, 10, true);
        
        $this->assertCount(2, $result->items());
        foreach ($result->items() as $video) {
            $this->assertEquals(1, $video->status);
        }
    }
    
    /**
     * 测试根据ID获取视频
     */
    public function testGetVideoById(): void
    {
        // 创建视频
        $video = $this->service->createVideo([
            'title' => '测试视频',
            'video_url' => 'https://example.com/videos/test.mp4'
        ]);
        
        // 根据ID获取视频
        $foundVideo = $this->service->getVideoById($video->id);
        
        $this->assertNotNull($foundVideo);
        $this->assertEquals('测试视频', $foundVideo->title);
    }
    
    /**
     * 测试获取不存在的视频
     */
    public function testGetNonExistentVideoById(): void
    {
        $video = $this->service->getVideoById(99999);
        
        $this->assertNull($video);
    }
}
