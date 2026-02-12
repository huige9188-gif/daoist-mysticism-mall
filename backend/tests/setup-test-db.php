<?php
/**
 * 测试数据库设置脚本
 * 
 * 用法: php tests/setup-test-db.php
 */

// 测试数据库配置
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'daoist_mall_test';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '123456';

echo "=================================\n";
echo "测试数据库设置\n";
echo "=================================\n\n";

echo "数据库配置:\n";
echo "  主机: {$host}:{$port}\n";
echo "  数据库: {$database}\n";
echo "  用户: {$username}\n\n";

try {
    // 连接数据库（不指定数据库）
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ 数据库连接成功\n\n";
    
    // 删除测试数据库（如果存在）
    echo "删除旧的测试数据库（如果存在）...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
    echo "✓ 完成\n\n";
    
    // 创建测试数据库
    echo "创建测试数据库...\n";
    $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");
    echo "✓ 数据库 '{$database}' 已创建\n\n";
    
    // 获取所有迁移文件
    $migrationsDir = __DIR__ . '/../database/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);
    
    if (empty($files)) {
        echo "⚠ 未找到迁移文件\n";
        exit(1);
    }
    
    echo "找到 " . count($files) . " 个迁移文件\n\n";
    echo "开始执行迁移...\n";
    echo "=================================\n\n";
    
    // 执行每个迁移文件
    foreach ($files as $file) {
        $filename = basename($file);
        echo "执行: {$filename}\n";
        
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        echo "  ✓ 成功\n\n";
    }
    
    echo "=================================\n";
    echo "✓ 测试数据库设置完成！\n\n";
    
    // 显示创建的表
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "数据库中的表 (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    echo "\n";
    
    echo "现在可以运行测试了:\n";
    echo "  composer test\n";
    echo "  或\n";
    echo "  vendor/bin/phpunit\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
