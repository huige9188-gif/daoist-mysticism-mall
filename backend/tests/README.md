# 数据库表结构测试

## 概述

本测试套件验证数据库表结构的正确性，包括：
- 所有表是否存在
- 所有字段是否存在且类型正确
- 所有索引是否正确创建
- 所有外键约束是否正确创建

## 运行测试

### 前置条件

1. 安装依赖：
```bash
composer install
```

2. 创建测试数据库：
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS daoist_mall_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

3. 运行数据库迁移：
```bash
php database/migrate.php
```

### 运行测试

运行所有测试：
```bash
composer test
```

或者使用 PHPUnit 直接运行：
```bash
vendor/bin/phpunit
```

只运行数据库表结构测试：
```bash
vendor/bin/phpunit tests/Database/TableStructureTest.php
```

### 配置测试数据库

测试使用的数据库配置在 `phpunit.xml` 文件中：

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_HOST" value="localhost"/>
    <env name="DB_PORT" value="3306"/>
    <env name="DB_DATABASE" value="daoist_mall_test"/>
    <env name="DB_USERNAME" value="root"/>
    <env name="DB_PASSWORD" value=""/>
</php>
```

如果需要修改数据库连接信息，请编辑 `phpunit.xml` 文件。

## 测试覆盖

### 已测试的表

1. **users** - 用户表
2. **categories** - 商品分类表
3. **products** - 商品表
4. **orders** - 订单表
5. **order_items** - 订单明细表
6. **videos** - 视频表
7. **articles** - 文章表
8. **feng_shui_masters** - 风水师表
9. **payment_configs** - 支付配置表
10. **chat_sessions** - 聊天会话表
11. **chat_messages** - 聊天消息表

### 验证项目

对于每个表，测试验证：
- ✅ 表存在
- ✅ 所有必需字段存在
- ✅ 字段类型正确
- ✅ 索引正确创建
- ✅ 唯一约束正确创建（如适用）
- ✅ 外键约束正确创建（如适用）

## 故障排除

### 连接数据库失败

如果测试无法连接到数据库，请检查：
1. MySQL 服务是否正在运行
2. `phpunit.xml` 中的数据库配置是否正确
3. 数据库用户是否有足够的权限

### 表不存在

如果测试报告表不存在，请确保：
1. 已创建测试数据库
2. 已运行数据库迁移脚本

### 外键约束测试失败

如果外键约束测试失败，可能是因为：
1. 迁移脚本执行顺序不正确（被引用的表必须先创建）
2. 外键约束名称不匹配
3. 引用的列类型不匹配

## 验证需求

本测试套件验证以下需求：
- **需求 12.1**: 数据持久化 - 系统保存数据到数据库时使用事务确保数据一致性
