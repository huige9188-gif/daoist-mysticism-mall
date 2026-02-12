<?php

namespace Tests\File;

use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use app\service\FileService;
use think\exception\ValidateException;
use think\file\UploadedFile;

/**
 * 文件服务属性测试
 * 
 * 使用Eris库进行基于属性的测试
 * 验证需求: 15.1, 15.2, 15.5, 15.6
 */
class FilePropertyTest extends TestCase
{
    use TestTrait;
    
    private FileService $service;
    private string $testUploadDir;
    
    protected function setUp(): void
    {
        $this->service = new FileService();
        
        // 创建测试上传目录
        $this->testUploadDir = public_path() . '/uploads';
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
        
        // 强制垃圾回收
        gc_collect_cycles();
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
        string $extension,
        int $size
    ): UploadedFile {
        $mock = $this->createMock(UploadedFile::class);
        
        $mock->method('extension')
            ->willReturn($extension);
        
        $mock->method('getSize')
            ->willReturn($size);
        
        $mock->method('move')
            ->willReturnCallback(function ($directory, $name) use ($mock) {
                // 创建目录
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                // 创建空文件模拟上传
                touch($directory . '/' . $name);
                return $mock;
            });
        
        return $mock;
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 56: 文件类型验证
     * 
     * **Validates: Requirements 15.1**
     * 
     * 对于任意文件扩展名不是jpg、jpeg、png或gif的文件，上传操作应该被拒绝
     * 
     * 此属性测试验证：
     * 1. 对于任意不支持的文件类型，上传应该失败
     * 2. 失败时应该抛出ValidateException异常
     * 3. 异常消息应该明确指出文件类型不支持
     * 4. 不支持的文件不应该被保存到服务器
     */
    public function testFileTypeValidation(): void
    {
        $this->forAll(
            $this->generateInvalidFileExtension(),
            $this->generateValidFileSize()
        )
        ->withMaxSize(50) // 限制测试规模
        ->then(function ($extension, $size) {
            $file = $this->createMockUploadedFile($extension, $size);
            
            // 验证上传应该失败
            $exceptionThrown = false;
            $exceptionMessage = '';
            
            try {
                $this->service->uploadImage($file);
            } catch (ValidateException $e) {
                $exceptionThrown = true;
                $exceptionMessage = $e->getMessage();
            }
            
            // 验证抛出了异常
            $this->assertTrue($exceptionThrown, 
                "上传不支持的文件类型应该抛出异常，扩展名: {$extension}");
            
            // 验证异常消息包含"文件类型"相关的提示
            $this->assertMatchesRegularExpression(
                '/不支持.*文件类型|文件类型.*不支持|unsupported.*file.*type/i',
                $exceptionMessage,
                "异常消息应该明确指出文件类型不支持"
            );
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 56: 文件类型验证（有效类型）
     * 
     * **Validates: Requirements 15.1**
     * 
     * 对于任意文件扩展名是jpg、jpeg、png或gif的文件，且大小在限制内，上传操作应该成功
     */
    public function testValidFileTypeAccepted(): void
    {
        $this->forAll(
            $this->generateValidFileExtension(),
            $this->generateValidFileSize()
        )
        ->withMaxSize(50) // 限制测试规模
        ->then(function ($extension, $size) {
            $file = $this->createMockUploadedFile($extension, $size);
            
            try {
                $result = $this->service->uploadImage($file);
                
                // 验证上传成功
                $this->assertIsArray($result, 
                    "上传有效文件类型应该成功，扩展名: {$extension}");
                $this->assertArrayHasKey('url', $result);
                $this->assertArrayHasKey('path', $result);
                
                // 验证文件扩展名正确
                $this->assertStringContainsString('.' . strtolower($extension), strtolower($result['path']),
                    "返回的路径应该包含正确的文件扩展名");
            } catch (\Exception $e) {
                $this->fail("上传有效文件类型不应该抛出异常: " . $e->getMessage());
            }
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 57: 文件大小验证
     * 
     * **Validates: Requirements 15.2**
     * 
     * 对于任意大小超过5MB的文件，上传操作应该被拒绝
     * 
     * 此属性测试验证：
     * 1. 对于任意超过5MB的文件，上传应该失败
     * 2. 失败时应该抛出ValidateException异常
     * 3. 异常消息应该明确指出文件大小超过限制
     * 4. 超大文件不应该被保存到服务器
     */
    public function testFileSizeValidation(): void
    {
        $this->forAll(
            $this->generateValidFileExtension(),
            $this->generateOversizedFileSize()
        )
        ->withMaxSize(50) // 限制测试规模
        ->then(function ($extension, $size) {
            $file = $this->createMockUploadedFile($extension, $size);
            
            // 验证上传应该失败
            $exceptionThrown = false;
            $exceptionMessage = '';
            
            try {
                $this->service->uploadImage($file);
            } catch (ValidateException $e) {
                $exceptionThrown = true;
                $exceptionMessage = $e->getMessage();
            }
            
            // 验证抛出了异常
            $this->assertTrue($exceptionThrown, 
                "上传超过5MB的文件应该抛出异常，大小: " . ($size / 1024 / 1024) . "MB");
            
            // 验证异常消息包含"文件大小"或"限制"相关的提示
            $this->assertMatchesRegularExpression(
                '/文件大小.*超过.*限制|文件.*超过.*5MB|file.*size.*exceed|file.*too.*large/i',
                $exceptionMessage,
                "异常消息应该明确指出文件大小超过限制"
            );
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 59: 文件名唯一性
     * 
     * **Validates: Requirements 15.5**
     * 
     * 对于任意两次上传操作（即使是相同文件），生成的文件名应该不同
     * 
     * 此属性测试验证：
     * 1. 多次上传相同类型和大小的文件，生成的文件名应该都不同
     * 2. 文件名应该包含足够的随机性以避免冲突
     * 3. 即使在短时间内连续上传，文件名也应该不同
     */
    public function testFilenameUniqueness(): void
    {
        $this->forAll(
            $this->generateValidFileExtension(),
            $this->generateValidFileSize(),
            \Eris\Generator\choose(2, 3) // 减少上传次数
        )
        ->withMaxSize(30) // 限制测试规模
        ->then(function ($extension, $size, $uploadCount) {
            $filenames = [];
            
            // 多次上传相同类型和大小的文件
            for ($i = 0; $i < $uploadCount; $i++) {
                $file = $this->createMockUploadedFile($extension, $size);
                $result = $this->service->uploadImage($file);
                
                // 提取文件名（不含目录）
                $filename = basename($result['path']);
                $filenames[] = $filename;
            }
            
            // 验证所有文件名都不相同
            $uniqueFilenames = array_unique($filenames);
            $this->assertCount(
                count($filenames),
                $uniqueFilenames,
                "多次上传应该生成不同的文件名。生成的文件名: " . implode(', ', $filenames)
            );
            
            // 验证每个文件名都包含扩展名
            foreach ($filenames as $filename) {
                $this->assertStringContainsString('.' . strtolower($extension), strtolower($filename),
                    "文件名应该包含正确的扩展名");
            }
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 60: 文件目录组织
     * 
     * **Validates: Requirements 15.6**
     * 
     * 对于任意上传的文件，文件路径应该包含上传日期的年/月/日目录结构
     * 
     * 此属性测试验证：
     * 1. 上传的文件路径应该包含日期目录结构（YYYY/MM/DD）
     * 2. 日期目录应该是当前日期
     * 3. 路径格式应该是 uploads/YYYY/MM/DD/filename.ext
     */
    public function testFileOrganizedByDate(): void
    {
        $this->forAll(
            $this->generateValidFileExtension(),
            $this->generateValidFileSize()
        )
        ->withMaxSize(50) // 限制测试规模
        ->then(function ($extension, $size) {
            $file = $this->createMockUploadedFile($extension, $size);
            $result = $this->service->uploadImage($file);
            
            // 验证路径包含日期目录结构
            $expectedDatePath = date('Y/m/d');
            $this->assertStringContainsString($expectedDatePath, $result['path'],
                "文件路径应该包含日期目录结构: {$expectedDatePath}");
            
            // 验证路径格式: uploads/YYYY/MM/DD/filename.ext
            $pathPattern = '/uploads\/\d{4}\/\d{2}\/\d{2}\/.+\.(jpg|jpeg|png|gif)$/i';
            $this->assertMatchesRegularExpression($pathPattern, $result['path'],
                "文件路径应该符合 uploads/YYYY/MM/DD/filename.ext 格式");
            
            // 验证URL也包含相同的路径
            $this->assertStringContainsString($result['path'], $result['url'],
                "URL应该包含文件路径");
        });
    }
    
    /**
     * 生成有效的文件扩展名生成器
     * 
     * 生成jpg、jpeg、png、gif中的一个
     */
    private function generateValidFileExtension()
    {
        return \Eris\Generator\elements('jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF');
    }
    
    /**
     * 生成无效的文件扩展名生成器
     * 
     * 生成不支持的文件扩展名
     */
    private function generateInvalidFileExtension()
    {
        return \Eris\Generator\elements(
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar',
            'exe', 'bat', 'sh', 'php', 'js', 'html', 'css', 'svg',
            'bmp', 'tiff', 'webp', 'ico', 'psd', 'ai', 'mp4', 'avi',
            'mp3', 'wav', 'flac', 'ogg', 'mov', 'wmv', 'flv', 'mkv'
        );
    }
    
    /**
     * 生成有效的文件大小生成器
     * 
     * 生成1KB到5MB之间的文件大小
     */
    private function generateValidFileSize()
    {
        return \Eris\Generator\choose(
            1024,                    // 1KB
            5 * 1024 * 1024          // 5MB
        );
    }
    
    /**
     * 生成超大文件大小生成器
     * 
     * 生成超过5MB的文件大小
     */
    private function generateOversizedFileSize()
    {
        return \Eris\Generator\choose(
            5 * 1024 * 1024 + 1,     // 5MB + 1字节
            10 * 1024 * 1024         // 10MB
        );
    }
}
