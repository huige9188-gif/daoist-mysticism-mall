<?php

namespace Tests\File;

use PHPUnit\Framework\TestCase;
use app\service\FileService;
use think\exception\ValidateException;
use think\file\UploadedFile;

/**
 * 文件服务测试
 * 
 * 验证需求: 15.1, 15.2, 15.3, 15.5, 15.6
 */
class FileServiceTest extends TestCase
{
    private FileService $service;
    private string $testUploadDir;
    
    protected function setUp(): void
    {
        $this->service = new FileService();
        
        // 创建测试上传目录
        $this->testUploadDir = public_path() . '/uploads/' . date('Y/m/d');
        if (!is_dir($this->testUploadDir)) {
            mkdir($this->testUploadDir, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // 清理测试文件
        if (is_dir(public_path() . '/uploads')) {
            $this->deleteDirectory(public_path() . '/uploads');
        }
    }
    
    /**
     * 递归删除目录
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * 创建模拟上传文件
     */
    private function createMockUploadedFile(
        string $filename,
        int $size,
        string $extension
    ): UploadedFile {
        $mock = $this->createMock(UploadedFile::class);
        
        $mock->method('extension')
            ->willReturn($extension);
        
        $mock->method('getSize')
            ->willReturn($size);
        
        $mock->method('move')
            ->willReturnCallback(function ($directory, $name) use ($filename, $mock) {
                // 创建目录
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                // 创建空文件模拟上传
                touch($directory . '/' . $name);
                // 返回mock对象本身以满足返回类型要求
                return $mock;
            });
        
        return $mock;
    }
    
    /**
     * 测试上传JPG图片成功
     * 验证需求: 15.1, 15.3
     */
    public function testUploadJpgImageSuccess(): void
    {
        $file = $this->createMockUploadedFile('test.jpg', 1024 * 1024, 'jpg');
        
        $result = $this->service->uploadImage($file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringContainsString('uploads/', $result['path']);
        $this->assertStringContainsString('.jpg', $result['path']);
    }
    
    /**
     * 测试上传JPEG图片成功
     * 验证需求: 15.1, 15.3
     */
    public function testUploadJpegImageSuccess(): void
    {
        $file = $this->createMockUploadedFile('test.jpeg', 2 * 1024 * 1024, 'jpeg');
        
        $result = $this->service->uploadImage($file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringContainsString('.jpeg', $result['path']);
    }
    
    /**
     * 测试上传PNG图片成功
     * 验证需求: 15.1, 15.3
     */
    public function testUploadPngImageSuccess(): void
    {
        $file = $this->createMockUploadedFile('test.png', 3 * 1024 * 1024, 'png');
        
        $result = $this->service->uploadImage($file);
        
        $this->assertIsArray($result);
        $this->assertStringContainsString('.png', $result['path']);
    }
    
    /**
     * 测试上传GIF图片成功
     * 验证需求: 15.1, 15.3
     */
    public function testUploadGifImageSuccess(): void
    {
        $file = $this->createMockUploadedFile('test.gif', 4 * 1024 * 1024, 'gif');
        
        $result = $this->service->uploadImage($file);
        
        $this->assertIsArray($result);
        $this->assertStringContainsString('.gif', $result['path']);
    }
    
    /**
     * 测试上传不支持的文件类型
     * 验证需求: 15.1
     */
    public function testUploadUnsupportedFileType(): void
    {
        $file = $this->createMockUploadedFile('test.pdf', 1024 * 1024, 'pdf');
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('不支持的文件类型');
        
        $this->service->uploadImage($file);
    }
    
    /**
     * 测试上传BMP文件被拒绝
     * 验证需求: 15.1
     */
    public function testUploadBmpFileRejected(): void
    {
        $file = $this->createMockUploadedFile('test.bmp', 1024 * 1024, 'bmp');
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('不支持的文件类型');
        
        $this->service->uploadImage($file);
    }
    
    /**
     * 测试上传SVG文件被拒绝
     * 验证需求: 15.1
     */
    public function testUploadSvgFileRejected(): void
    {
        $file = $this->createMockUploadedFile('test.svg', 1024 * 1024, 'svg');
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('不支持的文件类型');
        
        $this->service->uploadImage($file);
    }
    
    /**
     * 测试上传超过5MB的文件
     * 验证需求: 15.2
     */
    public function testUploadFileSizeExceedsLimit(): void
    {
        $file = $this->createMockUploadedFile('large.jpg', 6 * 1024 * 1024, 'jpg');
        
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('文件大小超过限制');
        
        $this->service->uploadImage($file);
    }
    
    /**
     * 测试上传正好5MB的文件
     * 验证需求: 15.2
     */
    public function testUploadFileExactly5MB(): void
    {
        $file = $this->createMockUploadedFile('exact.jpg', 5 * 1024 * 1024, 'jpg');
        
        $result = $this->service->uploadImage($file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
    }
    
    /**
     * 测试上传文件返回正确的URL格式
     * 验证需求: 15.3
     */
    public function testUploadReturnsCorrectUrlFormat(): void
    {
        $file = $this->createMockUploadedFile('test.jpg', 1024 * 1024, 'jpg');
        
        $result = $this->service->uploadImage($file);
        
        $this->assertStringStartsWith('http', $result['url']);
        $this->assertStringContainsString('uploads/', $result['url']);
    }
    
    /**
     * 测试文件名唯一性
     * 验证需求: 15.5
     */
    public function testFilenameUniqueness(): void
    {
        $file1 = $this->createMockUploadedFile('test.jpg', 1024 * 1024, 'jpg');
        $file2 = $this->createMockUploadedFile('test.jpg', 1024 * 1024, 'jpg');
        
        $result1 = $this->service->uploadImage($file1);
        $result2 = $this->service->uploadImage($file2);
        
        // 两次上传应该生成不同的文件名
        $this->assertNotEquals($result1['path'], $result2['path']);
        
        // 提取文件名（不含目录）
        $filename1 = basename($result1['path']);
        $filename2 = basename($result2['path']);
        
        $this->assertNotEquals($filename1, $filename2);
    }
    
    /**
     * 测试文件按日期组织目录
     * 验证需求: 15.6
     */
    public function testFileOrganizedByDate(): void
    {
        $file = $this->createMockUploadedFile('test.jpg', 1024 * 1024, 'jpg');
        
        $result = $this->service->uploadImage($file);
        
        // 验证路径包含日期目录结构 (Y/m/d)
        $expectedDatePath = date('Y/m/d');
        $this->assertStringContainsString($expectedDatePath, $result['path']);
        
        // 验证路径格式: uploads/YYYY/MM/DD/filename.ext
        $pathPattern = '/uploads\/\d{4}\/\d{2}\/\d{2}\/.+\.(jpg|jpeg|png|gif)$/';
        $this->assertMatchesRegularExpression($pathPattern, $result['path']);
    }
    
    /**
     * 测试大小写扩展名处理
     * 验证需求: 15.1
     */
    public function testCaseInsensitiveExtension(): void
    {
        // 测试大写扩展名
        $file1 = $this->createMockUploadedFile('test.JPG', 1024 * 1024, 'JPG');
        $result1 = $this->service->uploadImage($file1);
        $this->assertIsArray($result1);
        
        // 测试混合大小写扩展名
        $file2 = $this->createMockUploadedFile('test.JpEg', 1024 * 1024, 'JpEg');
        $result2 = $this->service->uploadImage($file2);
        $this->assertIsArray($result2);
    }
    
    /**
     * 测试返回的path和url一致性
     * 验证需求: 15.3
     */
    public function testPathAndUrlConsistency(): void
    {
        $file = $this->createMockUploadedFile('test.png', 2 * 1024 * 1024, 'png');
        
        $result = $this->service->uploadImage($file);
        
        // URL应该包含path
        $this->assertStringContainsString($result['path'], $result['url']);
    }
}
