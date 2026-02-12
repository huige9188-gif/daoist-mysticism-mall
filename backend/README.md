# 道家玄学商城系统 - 后端API

基于ThinkPHP 6.x框架开发的RESTful API服务。

## 环境要求

- PHP >= 7.2.5
- MySQL >= 8.0
- Redis >= 5.0
- Composer

## 安装步骤

1. 安装依赖
```bash
composer install
```

2. 配置环境变量
```bash
cp .env.example .env
# 编辑.env文件，配置数据库和Redis连接信息
```

3. 创建数据库
```bash
mysql -u root -p
CREATE DATABASE daoist_mall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. 运行数据库迁移
```bash
php think migrate:run
```

5. 启动开发服务器
```bash
php think run
```

服务器将在 http://localhost:8000 启动

## 项目结构

```
backend/
├── app/                    # 应用目录
│   ├── controller/        # 控制器
│   │   ├── admin/        # 管理后台控制器
│   │   └── api/          # API控制器
│   ├── model/            # 模型
│   ├── service/          # 业务逻辑服务
│   ├── middleware/       # 中间件
│   └── validate/         # 验证器
├── config/               # 配置文件
├── route/                # 路由定义
├── public/               # 公共资源
└── runtime/              # 运行时文件
```

## API文档

API遵循RESTful设计原则，所有响应格式为JSON。

### 统一响应格式

成功响应：
```json
{
    "code": 200,
    "message": "操作成功",
    "data": {}
}
```

失败响应：
```json
{
    "code": 400,
    "message": "错误描述",
    "data": null
}
```

### 认证

API使用JWT进行身份认证。在请求头中添加：
```
Authorization: Bearer <token>
```

## 测试

运行测试：
```bash
composer test
```

## 许可证

MIT
