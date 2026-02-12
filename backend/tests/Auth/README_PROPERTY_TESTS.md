# 认证属性测试说明

## 概述

本目录包含使用Eris库编写的基于属性的测试（Property-Based Tests）。这些测试验证认证系统在各种输入下的通用属性。

## 安装依赖

在运行属性测试之前，需要安装Eris库：

```bash
cd backend
composer install
```

Eris已经添加到`composer.json`的`require-dev`部分。

## 运行测试

### 运行所有认证测试（包括属性测试）

```bash
vendor/bin/phpunit tests/Auth/
```

### 仅运行属性测试

```bash
vendor/bin/phpunit tests/Auth/AuthPropertyTest.php
```

### 运行特定的属性测试

```bash
vendor/bin/phpunit --filter testCorrectCredentialsGenerateToken tests/Auth/AuthPropertyTest.php
```

## 测试配置

属性测试默认运行100次迭代（由Eris库控制）。每次迭代使用随机生成的输入数据。

### 数据库配置

属性测试使用与其他测试相同的数据库配置，在`phpunit.xml`中定义：

```xml
<env name="DB_HOST" value="localhost"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="daoist_mall_test"/>
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value=""/>
```

确保测试数据库已创建：

```sql
CREATE DATABASE IF NOT EXISTS daoist_mall_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 属性测试列表

### AuthPropertyTest.php

#### testCorrectCredentialsGenerateToken

**属性 41: 正确凭证生成令牌**

**验证需求: 11.2**

此测试验证：对于任意有效的用户名和正确的密码，登录操作应该返回有效的JWT令牌。

测试覆盖：
- 使用正确的用户名和密码登录总是成功
- 返回的响应包含有效的JWT令牌
- 令牌可以被成功验证
- 从令牌中提取的用户信息与原始用户信息一致
- 令牌包含标准JWT声明（iat, exp）
- 令牌过期时间在未来

**输入生成器：**
- 用户名：3-20个字符的字母数字字符串
- 密码：6-50个字符的任意字符串
- 邮箱：有效的邮箱格式（username@example.com）
- 角色：'user'或'admin'

## 理解属性测试

属性测试与传统单元测试的区别：

### 传统单元测试
```php
public function testLoginWithCorrectCredentials() {
    // 测试一个特定的例子
    $response = $this->login('testuser', 'password123');
    $this->assertEquals(200, $response['code']);
}
```

### 属性测试
```php
public function testCorrectCredentialsGenerateToken() {
    // 测试所有可能的有效输入
    $this->forAll(
        $this->generateUsername(),
        $this->generatePassword(),
        // ...
    )->then(function ($username, $password, ...) {
        // 验证属性对所有输入都成立
    });
}
```

属性测试通过生成大量随机输入来发现边界情况和意外行为，提供比单元测试更全面的覆盖。

## 故障排除

### 测试失败时

如果属性测试失败，Eris会提供：
1. 导致失败的具体输入值
2. 失败的断言信息
3. 可以用于重现问题的种子值

### 常见问题

1. **数据库连接失败**
   - 检查数据库是否运行
   - 验证`phpunit.xml`中的数据库配置
   - 确保测试数据库已创建

2. **Eris库未找到**
   - 运行`composer install`安装依赖
   - 检查`composer.json`中是否包含Eris

3. **测试超时**
   - 属性测试可能需要更长时间运行（100次迭代）
   - 这是正常的，请耐心等待

## 参考资料

- [Eris文档](https://github.com/giorgiosironi/eris)
- [基于属性的测试介绍](https://hypothesis.works/articles/what-is-property-based-testing/)
- 设计文档：`.kiro/specs/daoist-mysticism-mall/design.md`
- 需求文档：`.kiro/specs/daoist-mysticism-mall/requirements.md`
