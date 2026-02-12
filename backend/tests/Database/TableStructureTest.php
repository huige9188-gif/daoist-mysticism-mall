<?php

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

/**
 * 数据库表结构测试
 * 
 * 验证需求: 12.1 - 数据持久化
 * 
 * 测试目标:
 * - 验证所有表存在
 * - 验证所有字段存在且类型正确
 * - 验证索引正确创建
 * - 验证外键约束正确创建
 */
class TableStructureTest extends TestCase
{
    private static ?PDO $pdo = null;

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
        } catch (PDOException $e) {
            self::fail("无法连接到数据库: " . $e->getMessage());
        }
    }

    /**
     * 测试用户表存在且结构正确
     */
    public function testUsersTableExists(): void
    {
        $this->assertTableExists('users');
        
        // 验证字段
        $this->assertColumnExists('users', 'id', 'bigint');
        $this->assertColumnExists('users', 'username', 'varchar');
        $this->assertColumnExists('users', 'password', 'varchar');
        $this->assertColumnExists('users', 'email', 'varchar');
        $this->assertColumnExists('users', 'phone', 'varchar');
        $this->assertColumnExists('users', 'role', 'enum');
        $this->assertColumnExists('users', 'status', 'tinyint');
        $this->assertColumnExists('users', 'created_at', 'timestamp');
        $this->assertColumnExists('users', 'updated_at', 'timestamp');
        $this->assertColumnExists('users', 'deleted_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('users', 'idx_username');
        $this->assertIndexExists('users', 'idx_email');
        $this->assertIndexExists('users', 'idx_deleted_at');
        
        // 验证唯一约束
        $this->assertUniqueConstraintExists('users', 'username');
        $this->assertUniqueConstraintExists('users', 'email');
    }

    /**
     * 测试商品分类表存在且结构正确
     */
    public function testCategoriesTableExists(): void
    {
        $this->assertTableExists('categories');
        
        // 验证字段
        $this->assertColumnExists('categories', 'id', 'bigint');
        $this->assertColumnExists('categories', 'name', 'varchar');
        $this->assertColumnExists('categories', 'sort_order', 'int');
        $this->assertColumnExists('categories', 'status', 'tinyint');
        $this->assertColumnExists('categories', 'created_at', 'timestamp');
        $this->assertColumnExists('categories', 'updated_at', 'timestamp');
        $this->assertColumnExists('categories', 'deleted_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('categories', 'idx_sort_order');
        $this->assertIndexExists('categories', 'idx_status');
    }

    /**
     * 测试商品表存在且结构正确
     */
    public function testProductsTableExists(): void
    {
        $this->assertTableExists('products');
        
        // 验证字段
        $this->assertColumnExists('products', 'id', 'bigint');
        $this->assertColumnExists('products', 'category_id', 'bigint');
        $this->assertColumnExists('products', 'name', 'varchar');
        $this->assertColumnExists('products', 'description', 'text');
        $this->assertColumnExists('products', 'price', 'decimal');
        $this->assertColumnExists('products', 'stock', 'int');
        $this->assertColumnExists('products', 'images', 'json');
        $this->assertColumnExists('products', 'status', 'enum');
        $this->assertColumnExists('products', 'created_at', 'timestamp');
        $this->assertColumnExists('products', 'updated_at', 'timestamp');
        $this->assertColumnExists('products', 'deleted_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('products', 'idx_category_id');
        $this->assertIndexExists('products', 'idx_status');
        $this->assertIndexExists('products', 'idx_deleted_at');
        
        // 验证外键
        $this->assertForeignKeyExists('products', 'fk_products_category', 'category_id', 'categories', 'id');
    }

    /**
     * 测试订单表存在且结构正确
     */
    public function testOrdersTableExists(): void
    {
        $this->assertTableExists('orders');
        
        // 验证字段
        $this->assertColumnExists('orders', 'id', 'bigint');
        $this->assertColumnExists('orders', 'order_no', 'varchar');
        $this->assertColumnExists('orders', 'user_id', 'bigint');
        $this->assertColumnExists('orders', 'total_amount', 'decimal');
        $this->assertColumnExists('orders', 'status', 'enum');
        $this->assertColumnExists('orders', 'payment_gateway', 'varchar');
        $this->assertColumnExists('orders', 'address', 'json');
        $this->assertColumnExists('orders', 'logistics_company', 'varchar');
        $this->assertColumnExists('orders', 'logistics_number', 'varchar');
        $this->assertColumnExists('orders', 'paid_at', 'timestamp');
        $this->assertColumnExists('orders', 'shipped_at', 'timestamp');
        $this->assertColumnExists('orders', 'completed_at', 'timestamp');
        $this->assertColumnExists('orders', 'created_at', 'timestamp');
        $this->assertColumnExists('orders', 'updated_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('orders', 'idx_order_no');
        $this->assertIndexExists('orders', 'idx_user_id');
        $this->assertIndexExists('orders', 'idx_status');
        $this->assertIndexExists('orders', 'idx_created_at');
        
        // 验证唯一约束
        $this->assertUniqueConstraintExists('orders', 'order_no');
        
        // 验证外键
        $this->assertForeignKeyExists('orders', 'fk_orders_user', 'user_id', 'users', 'id');
    }

    /**
     * 测试订单明细表存在且结构正确
     */
    public function testOrderItemsTableExists(): void
    {
        $this->assertTableExists('order_items');
        
        // 验证字段
        $this->assertColumnExists('order_items', 'id', 'bigint');
        $this->assertColumnExists('order_items', 'order_id', 'bigint');
        $this->assertColumnExists('order_items', 'product_id', 'bigint');
        $this->assertColumnExists('order_items', 'product_name', 'varchar');
        $this->assertColumnExists('order_items', 'quantity', 'int');
        $this->assertColumnExists('order_items', 'price', 'decimal');
        $this->assertColumnExists('order_items', 'created_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('order_items', 'idx_order_id');
        
        // 验证外键
        $this->assertForeignKeyExists('order_items', 'fk_order_items_order', 'order_id', 'orders', 'id');
        $this->assertForeignKeyExists('order_items', 'fk_order_items_product', 'product_id', 'products', 'id');
    }

    /**
     * 测试视频表存在且结构正确
     */
    public function testVideosTableExists(): void
    {
        $this->assertTableExists('videos');
        
        // 验证字段
        $this->assertColumnExists('videos', 'id', 'bigint');
        $this->assertColumnExists('videos', 'title', 'varchar');
        $this->assertColumnExists('videos', 'description', 'text');
        $this->assertColumnExists('videos', 'video_url', 'varchar');
        $this->assertColumnExists('videos', 'cover_image', 'varchar');
        $this->assertColumnExists('videos', 'status', 'tinyint');
        $this->assertColumnExists('videos', 'created_at', 'timestamp');
        $this->assertColumnExists('videos', 'updated_at', 'timestamp');
        $this->assertColumnExists('videos', 'deleted_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('videos', 'idx_status');
        $this->assertIndexExists('videos', 'idx_deleted_at');
    }

    /**
     * 测试文章表存在且结构正确
     */
    public function testArticlesTableExists(): void
    {
        $this->assertTableExists('articles');
        
        // 验证字段
        $this->assertColumnExists('articles', 'id', 'bigint');
        $this->assertColumnExists('articles', 'title', 'varchar');
        $this->assertColumnExists('articles', 'content', 'text');
        $this->assertColumnExists('articles', 'cover_image', 'varchar');
        $this->assertColumnExists('articles', 'author', 'varchar');
        $this->assertColumnExists('articles', 'status', 'enum');
        $this->assertColumnExists('articles', 'created_at', 'timestamp');
        $this->assertColumnExists('articles', 'updated_at', 'timestamp');
        $this->assertColumnExists('articles', 'deleted_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('articles', 'idx_status');
        $this->assertIndexExists('articles', 'idx_deleted_at');
    }

    /**
     * 测试风水师表存在且结构正确
     */
    public function testFengShuiMastersTableExists(): void
    {
        $this->assertTableExists('feng_shui_masters');
        
        // 验证字段
        $this->assertColumnExists('feng_shui_masters', 'id', 'bigint');
        $this->assertColumnExists('feng_shui_masters', 'name', 'varchar');
        $this->assertColumnExists('feng_shui_masters', 'bio', 'text');
        $this->assertColumnExists('feng_shui_masters', 'specialty', 'varchar');
        $this->assertColumnExists('feng_shui_masters', 'contact', 'varchar');
        $this->assertColumnExists('feng_shui_masters', 'avatar', 'varchar');
        $this->assertColumnExists('feng_shui_masters', 'status', 'tinyint');
        $this->assertColumnExists('feng_shui_masters', 'created_at', 'timestamp');
        $this->assertColumnExists('feng_shui_masters', 'updated_at', 'timestamp');
        $this->assertColumnExists('feng_shui_masters', 'deleted_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('feng_shui_masters', 'idx_status');
        $this->assertIndexExists('feng_shui_masters', 'idx_deleted_at');
    }

    /**
     * 测试支付配置表存在且结构正确
     */
    public function testPaymentConfigsTableExists(): void
    {
        $this->assertTableExists('payment_configs');
        
        // 验证字段
        $this->assertColumnExists('payment_configs', 'id', 'bigint');
        $this->assertColumnExists('payment_configs', 'gateway', 'varchar');
        $this->assertColumnExists('payment_configs', 'config', 'json');
        $this->assertColumnExists('payment_configs', 'status', 'tinyint');
        $this->assertColumnExists('payment_configs', 'created_at', 'timestamp');
        $this->assertColumnExists('payment_configs', 'updated_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('payment_configs', 'idx_gateway');
        
        // 验证唯一约束
        $this->assertUniqueConstraintExists('payment_configs', 'gateway');
    }

    /**
     * 测试聊天会话表存在且结构正确
     */
    public function testChatSessionsTableExists(): void
    {
        $this->assertTableExists('chat_sessions');
        
        // 验证字段
        $this->assertColumnExists('chat_sessions', 'id', 'bigint');
        $this->assertColumnExists('chat_sessions', 'user_id', 'bigint');
        $this->assertColumnExists('chat_sessions', 'status', 'enum');
        $this->assertColumnExists('chat_sessions', 'started_at', 'timestamp');
        $this->assertColumnExists('chat_sessions', 'last_activity_at', 'timestamp');
        $this->assertColumnExists('chat_sessions', 'closed_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('chat_sessions', 'idx_user_id');
        $this->assertIndexExists('chat_sessions', 'idx_status');
        $this->assertIndexExists('chat_sessions', 'idx_last_activity_at');
        
        // 验证外键
        $this->assertForeignKeyExists('chat_sessions', 'fk_chat_sessions_user', 'user_id', 'users', 'id');
    }

    /**
     * 测试聊天消息表存在且结构正确
     */
    public function testChatMessagesTableExists(): void
    {
        $this->assertTableExists('chat_messages');
        
        // 验证字段
        $this->assertColumnExists('chat_messages', 'id', 'bigint');
        $this->assertColumnExists('chat_messages', 'session_id', 'bigint');
        $this->assertColumnExists('chat_messages', 'sender_id', 'bigint');
        $this->assertColumnExists('chat_messages', 'content', 'text');
        $this->assertColumnExists('chat_messages', 'created_at', 'timestamp');
        
        // 验证索引
        $this->assertIndexExists('chat_messages', 'idx_session_id');
        $this->assertIndexExists('chat_messages', 'idx_created_at');
        
        // 验证外键
        $this->assertForeignKeyExists('chat_messages', 'fk_chat_messages_session', 'session_id', 'chat_sessions', 'id');
        $this->assertForeignKeyExists('chat_messages', 'fk_chat_messages_sender', 'sender_id', 'users', 'id');
    }

    // ==================== 辅助断言方法 ====================

    /**
     * 断言表存在
     */
    private function assertTableExists(string $tableName): void
    {
        $stmt = self::$pdo->query("SHOW TABLES LIKE '{$tableName}'");
        $result = $stmt->fetch();
        
        $this->assertNotFalse($result, "表 '{$tableName}' 不存在");
    }

    /**
     * 断言列存在且类型正确
     */
    private function assertColumnExists(string $tableName, string $columnName, string $expectedType): void
    {
        $stmt = self::$pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
        $column = $stmt->fetch();
        
        $this->assertNotFalse($column, "表 '{$tableName}' 中的列 '{$columnName}' 不存在");
        
        // 验证类型（简化版本，只检查类型前缀）
        $actualType = strtolower($column['Type']);
        $expectedType = strtolower($expectedType);
        
        $this->assertStringContainsString(
            $expectedType,
            $actualType,
            "表 '{$tableName}' 中的列 '{$columnName}' 类型不正确。期望包含: {$expectedType}, 实际: {$actualType}"
        );
    }

    /**
     * 断言索引存在
     */
    private function assertIndexExists(string $tableName, string $indexName): void
    {
        $stmt = self::$pdo->query("SHOW INDEX FROM `{$tableName}` WHERE Key_name = '{$indexName}'");
        $index = $stmt->fetch();
        
        $this->assertNotFalse($index, "表 '{$tableName}' 中的索引 '{$indexName}' 不存在");
    }

    /**
     * 断言唯一约束存在
     */
    private function assertUniqueConstraintExists(string $tableName, string $columnName): void
    {
        $stmt = self::$pdo->query("SHOW INDEX FROM `{$tableName}` WHERE Column_name = '{$columnName}' AND Non_unique = 0");
        $index = $stmt->fetch();
        
        $this->assertNotFalse($index, "表 '{$tableName}' 中的列 '{$columnName}' 没有唯一约束");
    }

    /**
     * 断言外键约束存在
     */
    private function assertForeignKeyExists(
        string $tableName,
        string $constraintName,
        string $columnName,
        string $referencedTable,
        string $referencedColumn
    ): void {
        $database = getenv('DB_DATABASE') ?: 'daoist_mall_test';
        
        $sql = "
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = :database
                AND TABLE_NAME = :table
                AND CONSTRAINT_NAME = :constraint
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ";
        
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute([
            'database' => $database,
            'table' => $tableName,
            'constraint' => $constraintName
        ]);
        
        $fk = $stmt->fetch();
        
        $this->assertNotFalse($fk, "表 '{$tableName}' 中的外键约束 '{$constraintName}' 不存在");
        $this->assertEquals($columnName, $fk['COLUMN_NAME'], "外键列名不匹配");
        $this->assertEquals($referencedTable, $fk['REFERENCED_TABLE_NAME'], "外键引用表不匹配");
        $this->assertEquals($referencedColumn, $fk['REFERENCED_COLUMN_NAME'], "外键引用列不匹配");
    }
}
