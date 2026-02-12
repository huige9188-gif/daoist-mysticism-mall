# 数据库迁移文件

本目录包含道家玄学商城系统的所有数据库表迁移文件。

## 迁移文件列表

迁移文件按照执行顺序命名（使用日期前缀），确保表之间的依赖关系正确：

1. `20240101_create_users_table.sql` - 用户表
2. `20240102_create_categories_table.sql` - 商品分类表
3. `20240103_create_products_table.sql` - 商品表（依赖categories）
4. `20240104_create_orders_table.sql` - 订单表（依赖users）
5. `20240105_create_order_items_table.sql` - 订单明细表（依赖orders和products）
6. `20240106_create_videos_table.sql` - 视频表
7. `20240107_create_articles_table.sql` - 文章表
8. `20240108_create_feng_shui_masters_table.sql` - 风水师表
9. `20240109_create_payment_configs_table.sql` - 支付配置表
10. `20240110_create_chat_sessions_table.sql` - 聊天会话表（依赖users）
11. `20240111_create_chat_messages_table.sql` - 聊天消息表（依赖chat_sessions和users）

## 执行迁移

### 方法一：使用提供的迁移脚本

```bash
# 在backend目录下执行
php database/migrate.php
```

### 方法二：手动执行SQL文件

```bash
# 按顺序执行每个SQL文件
mysql -u root -p daoist_mall < database/migrations/20240101_create_users_table.sql
mysql -u root -p daoist_mall < database/migrations/20240102_create_categories_table.sql
# ... 依次执行其他文件
```

### 方法三：一次性执行所有迁移

```bash
# 合并所有SQL文件并执行
cat database/migrations/*.sql | mysql -u root -p daoist_mall
```

## 表结构说明

### 核心表

- **users**: 用户表，存储系统用户信息
- **categories**: 商品分类表
- **products**: 商品表，包含商品详细信息
- **orders**: 订单表，存储订单主信息
- **order_items**: 订单明细表，存储订单中的商品信息

### 内容管理表

- **videos**: 教学视频表
- **articles**: 资讯文章表
- **feng_shui_masters**: 风水师信息表

### 系统功能表

- **payment_configs**: 支付配置表，存储各支付网关配置
- **chat_sessions**: 聊天会话表
- **chat_messages**: 聊天消息表

## 表关系

```
users (1) ─── (N) orders
users (1) ─── (N) chat_sessions
categories (1) ─── (N) products
products (N) ─── (N) orders (通过 order_items)
orders (1) ─── (N) order_items
chat_sessions (1) ─── (N) chat_messages
```

## 索引说明

所有表都包含适当的索引以优化查询性能：

- 主键索引：所有表的 `id` 字段
- 唯一索引：`users.username`, `users.email`, `orders.order_no`, `payment_configs.gateway`
- 外键索引：所有外键字段
- 查询索引：常用查询字段如 `status`, `deleted_at`, `created_at` 等

## 软删除

以下表支持软删除（使用 `deleted_at` 字段）：

- users
- categories
- products
- videos
- articles
- feng_shui_masters

## 字符集

所有表使用 `utf8mb4` 字符集和 `utf8mb4_unicode_ci` 排序规则，支持完整的Unicode字符（包括emoji）。

## 注意事项

1. 执行迁移前请确保数据库 `daoist_mall` 已创建
2. 迁移文件必须按照文件名顺序执行，以满足外键依赖关系
3. 如果需要重新执行迁移，请先删除所有表或使用 `DROP DATABASE` 重建数据库
4. 生产环境执行迁移前请务必备份数据库
