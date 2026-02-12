<?php

namespace tests\File;

use PHPUnit\Framework\TestCase;
use think\facade\Db;
use PDO;

/**
 * 文件上传API测试
 * 
 * 验证需求: 15.1, 15.2, 15.3, 15.4
 */
class UploadApiTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static $adminUserId;
    
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
        
        // 创建管理员用户
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        self::$pdo->exec("
            INSERT INTO users (username, password, email, phone, role, status) 
            VALUES ('admin_upload_test', '{$hashedPassword}', 'admin_upload@test.com', '13800000088', 'admin', 1)
        ");
        self::$adminUserId = self::$pdo->lastInsertId();
    }
    
    public static function tearDownAfterClass(): void
    {
        // 清理测试数据
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM users WHERE username LIKE '%upload_test%'");
        }
    }
    
    protected function setUp(): void
    {
        // 清理测试用户
        self::$pdo->exec("DELETE FROM users WHERE username LIKE 'test_user_%'");
    }
    
    /**
     * 创建测试图片文件
     */
    private function createTestImage(string $filename, int $sizeInKB = 100): string
    {
        $tempDir = runtime_path() . 'test_uploads/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $filepath = $tempDir . $filename;
        
        // 创建一个简单的PNG图片
        $image = imagecreate(100, 100);
        imagecolorallocate($image, 255, 255, 255);
        imagepng($image, $filepath);
        imagedestroy($image);
        
        // 如果需要更大的文件，填充数据
        if ($sizeInKB > 10) {
            $handle = fopen($filepath, 'a');
            $data = str_repeat('0', ($sizeInKB - 10) * 1024);
            fwrite($handle, $data);
            fclose($handle);
        }
        
        return $filepath;
    }
    
    /**
     * 清理测试文件
     */
    private function cleanupTestFile(string $filepath): void
    {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    
    /**
     * 创建模拟的UploadedFile对象
     */
    private function createMockUploadedFile(string $filepath, string $originalName): object
    {
        return new class($filepath, $originalName) {
            private $path;
            private $name;
            
            public function __construct($path, $name) {
                $this->path = $path;
                $this->name = $name;
            }
            
            public function extension() {
                return pathinfo($this->name, PATHINFO_EXTENSION);
            }
            
            public function getSize() {
                return filesize($this->path);
            }
            
            public function move($directory, $filename) {
                $targetPath = $directory . '/' . $filename;
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                return copy($this->path, $targetPath);
            }
        };
    }
    
    /**
     * 测试成功上传图片
     * 验证需求: 15.1, 15.2, 15.3
     */
    public function testUploadImageSuccess()
    {
        $filepath = $this->createTestImage('test.png', 100);
        $mockFile = $this->createMockUploadedFile($filepath, 'test.png');
        
        try {
            $fileService = new \app\service\FileService();
            $result = $fileService->uploadImage($mockFile);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('url', $result);
            $this->assertArrayHasKey('path', $result);
            
            // 验证URL格式
            $this->assertStringContainsString('uploads/', $result['url']);
            $this->assertStringContainsString('.png', $result['url']);
            
            // 验证路径包含日期目录
            $this->assertStringContainsString(date('Y/m/d'), $result['path']);
            
        } finally {
            $this->cleanupTestFile($filepath);
        }
    }
    
    /**
     * 测试上传不支持的文件类型
     * 验证需求: 15.1, 15.4
     */
    public function testUploadInvalidFileType()
    {
        $tempDir = runtime_path() . 'test_uploads/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $filepath = $tempDir . 'test.txt';
        file_put_contents($filepath, 'test content');
        
        $mockFile = $this->createMockUploadedFile($filepath, 'test.txt');
        
        try {
            $fileService = new \app\service\FileService();
            
            $this->expectException(\think\exception\ValidateException::class);
            $this->expectExceptionMessage('不支持的文件类型');
            
            $fileService->uploadImage($mockFile);
            
        } finally {
            $this->cleanupTestFile($filepath);
        }
    }
    
    /**
     * 测试上传超过大小限制的文件
     * 验证需求: 15.2, 15.4
     */
    public function testUploadFileTooLarge()
    {
        // 创建一个超过5MB的文件
        $filepath = $this->createTestImage('large.png', 6000); // 6MB
        $mockFile = $this->createMockUploadedFile($filepath, 'large.png');
        
        try {
            $fileService = new \app\service\FileService();
            
            $this->expectException(\think\exception\ValidateException::class);
            $this->expectExceptionMessage('文件大小超过限制');
            
            $fileService->uploadImage($mockFile);
            
        } finally {
            $this->cleanupTestFile($filepath);
        }
    }
    
    /**
     * 测试支持的所有图片格式
     * 验证需求: 15.1
     */
    public function testUploadAllSupportedFormats()
    {
        $formats = ['jpg', 'jpeg', 'png', 'gif'];
        
        foreach ($formats as $format) {
            $filepath = $this->createTestImage("test.{$format}", 100);
            $mockFile = $this->createMockUploadedFile($filepath, "test.{$format}");
            
            try {
                $fileService = new \app\service\FileService();
                $result = $fileService->uploadImage($mockFile);
                
                $this->assertIsArray($result, "Failed to upload {$format} format");
                $this->assertStringContainsString(".{$format}", $result['url']);
                
            } finally {
                $this->cleanupTestFile($filepath);
            }
        }
    }
    
    /**
     * 测试文件名唯一性
     * 验证需求: 15.5
     */
    public function testUploadFilenameUniqueness()
    {
        $filepath1 = $this->createTestImage('test1.png', 100);
        $filepath2 = $this->createTestImage('test2.png', 100);
        
        $mockFile1 = $this->createMockUploadedFile($filepath1, 'same_name.png');
        $mockFile2 = $this->createMockUploadedFile($filepath2, 'same_name.png');
        
        try {
            $fileService = new \app\service\FileService();
            
            $result1 = $fileService->uploadImage($mockFile1);
            $result2 = $fileService->uploadImage($mockFile2);
            
            // 验证两次上传生成的文件名不同
            $this->assertNotEquals($result1['path'], $result2['path']);
            $this->assertNotEquals($result1['url'], $result2['url']);
            
        } finally {
            $this->cleanupTestFile($filepath1);
            $this->cleanupTestFile($filepath2);
        }
    }
    
    /**
     * 测试文件目录按日期组织
     * 验证需求: 15.6
     */
    public function testUploadDirectoryOrganization()
    {
        $filepath = $this->createTestImage('test.png', 100);
        $mockFile = $this->createMockUploadedFile($filepath, 'test.png');
        
        try {
            $fileService = new \app\service\FileService();
            $result = $fileService->uploadImage($mockFile);
            
            // 验证路径包含年/月/日目录结构
            $expectedDatePath = date('Y/m/d');
            $this->assertStringContainsString($expectedDatePath, $result['path']);
            $this->assertStringContainsString('uploads/', $result['path']);
            
            // 验证路径格式: uploads/YYYY/MM/DD/filename
            $pattern = '/uploads\/\d{4}\/\d{2}\/\d{2}\//';
            $this->assertMatchesRegularExpression($pattern, $result['path']);
            
        } finally {
            $this->cleanupTestFile($filepath);
        }
    }
}
