<?php
/**
 * 数据库迁移脚本
 * 
 * 用法: php database/migrate.php
 */

// 加载环境配置
require __DIR__ . '/../vendor/autoload.php';

// 加载.env文件（如果存在）
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// 数据库配置
$host = $_ENV['database.hostname'] ?? '127.0.0.1';
$port = $_ENV['database.hostport'] ?? '3306';
$database = $_ENV['database.database'] ?? 'daoist_mall';
$username = $_ENV['database.username'] ?? 'root';
$password = $_ENV['database.password'] ?? '123456';

echo "=================================\n";
echo "数据库迁移工具\n";
echo "=================================\n\n";

echo "数据库配置:\n";
echo "  主机: {$host}:{$port}\n";
echo "  数据库: {$database}\n";
echo "  用户: {$username}\n\n";

try {
    // 连接数据库
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ 数据库连接成功\n\n";
    
    // 创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");
    
    echo "✓ 数据库 '{$database}' 已准备就绪\n\n";
    
    // 获取所有迁移文件
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files); // 按文件名排序
    
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
        
        try {
            $pdo->exec($sql);
            echo "  ✓ 成功\n\n";
        } catch (PDOException $e) {
            // 如果表已存在，显示警告但继续
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "  ⚠ 表已存在，跳过\n\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "=================================\n";
    echo "✓ 所有迁移执行完成！\n\n";
    
    // 显示创建的表
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "数据库中的表 (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    echo "\n";
    
} catch (PDOException $e) {
    echo "\n✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
