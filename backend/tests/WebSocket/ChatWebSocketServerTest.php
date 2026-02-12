<?php

namespace Tests\WebSocket;

use PHPUnit\Framework\TestCase;

/**
 * WebSocket服务器测试
 * 验证需求: 10.2, 10.3
 * 
 * 注意: 由于Workerman的特性，WebSocket服务器的完整测试需要在实际运行环境中进行
 * 这里只测试基本的类结构和配置
 */
class ChatWebSocketServerTest extends TestCase
{
    /**
     * 测试WebSocket服务器类存在
     * 验证需求: 10.2, 10.3
     */
    public function testServerClassExists()
    {
        // 验证WebSocket服务器类存在
        $this->assertTrue(class_exists(\app\websocket\ChatWebSocketServer::class));
    }
    
    /**
     * 测试WebSocket配置文件存在
     * 验证需求: 10.2, 10.3
     */
    public function testWebSocketConfigExists()
    {
        $configFile = __DIR__ . '/../../config/websocket.php';
        
        // 验证配置文件存在
        $this->assertFileExists($configFile);
        
        // 加载配置
        $config = include $configFile;
        
        // 验证配置包含必需的键
        $this->assertArrayHasKey('host', $config);
        $this->assertArrayHasKey('port', $config);
        $this->assertArrayHasKey('worker_count', $config);
    }
    
    /**
     * 测试WebSocket启动脚本存在
     * 验证需求: 10.2, 10.3
     */
    public function testWebSocketStartupScriptExists()
    {
        $scriptFile = __DIR__ . '/../../websocket_server.php';
        
        // 验证启动脚本存在
        $this->assertFileExists($scriptFile);
        
        // 验证脚本可读
        $this->assertTrue(is_readable($scriptFile));
    }
    
    /**
     * 测试WebSocket文档存在
     * 验证需求: 10.2, 10.3
     */
    public function testWebSocketDocumentationExists()
    {
        $docFile = __DIR__ . '/../../docs/WEBSOCKET_API.md';
        
        // 验证文档存在
        $this->assertFileExists($docFile);
        
        // 验证文档不为空
        $content = file_get_contents($docFile);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('WebSocket', $content);
    }
}
