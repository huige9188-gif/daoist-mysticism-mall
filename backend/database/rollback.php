<?php
/**
 * 数据库回滚脚本
 * 
 * 用法: php database/rollback.php
 * 
 * 警告: 此脚本将删除所有表和数据！
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
echo "数据库回滚工具\n";
echo "=================================\n\n";

echo "⚠ 警告: 此操作将删除所有表和数据！\n\n";
echo "数据库配置:\n";
echo "  主机: {$host}:{$port}\n";
echo "  数据库: {$database}\n";
echo "  用户: {$username}\n\n";

// 确认操作
echo "确认要继续吗? (输入 'yes' 继续): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== 'yes') {
    echo "\n操作已取消\n";
    exit(0);
}

try {
    // 连接数据库
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "\n✓ 数据库连接成功\n\n";
    
    // 禁用外键检查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 按照依赖关系的逆序删除表
    $tables = [
        'chat_messages',
        'chat_sessions',
        'payment_configs',
        'feng_shui_masters',
        'articles',
        'videos',
        'order_items',
        'orders',
        'products',
        'categories',
        'users',
    ];
    
    echo "开始删除表...\n";
    echo "=================================\n\n";
    
    foreach ($tables as $table) {
        echo "删除表: {$table}\n";
        try {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "  ✓ 成功\n\n";
        } catch (PDOException $e) {
            echo "  ⚠ 警告: " . $e->getMessage() . "\n\n";
        }
    }
    
    // 启用外键检查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "=================================\n";
    echo "✓ 所有表已删除！\n\n";
    
    // 显示剩余的表
    $stmt = $pdo->query("SHOW TABLES");
    $remainingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($remainingTables)) {
        echo "数据库中没有剩余的表\n\n";
    } else {
        echo "数据库中剩余的表:\n";
        foreach ($remainingTables as $table) {
            echo "  - {$table}\n";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "\n✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
