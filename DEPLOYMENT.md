# 道家玄学商城系统 - 部署文档

## 系统要求

### 后端要求
- PHP >= 8.0
- MySQL >= 5.7
- Redis >= 5.0
- Composer
- Nginx 或 Apache

### 前端要求
- Node.js >= 16.0
- npm 或 yarn

## 安装步骤

### 1. 后端部署

#### 1.1 克隆代码
```bash
git clone <repository-url>
cd backend
```

#### 1.2 安装依赖
```bash
composer install --no-dev
```

#### 1.3 配置环境
```bash
cp .env.production .env
```

编辑 `.env` 文件,配置数据库和Redis连接信息:
- 修改 `DATABASE` 部分的数据库连接信息
- 修改 `REDIS` 部分的Redis连接信息
- 修改 `JWT.SECRET` 为随机字符串
- 设置 `APP_DEBUG = false`

#### 1.4 创建数据库
```bash
mysql -u root -p
CREATE DATABASE daoist_mall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 1.5 运行数据库迁移
```bash
php database/migrate.php
```

#### 1.6 配置Web服务器

**Nginx配置示例:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 1.7 设置文件权限
```bash
chmod -R 755 /path/to/backend
chmod -R 777 /path/to/backend/runtime
chmod -R 777 /path/to/backend/public/uploads
```

#### 1.8 启动WebSocket服务器
```bash
php think websocket:start
```

建议使用 supervisor 管理WebSocket进程:
```ini
[program:daoist-websocket]
command=php /path/to/backend/think websocket:start
directory=/path/to/backend
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/daoist-websocket.log
```

### 2. 前端部署

#### 2.1 安装依赖
```bash
cd frontend
npm install
```

#### 2.2 配置环境
编辑 `.env.production` 文件:
```
VITE_API_BASE_URL=https://your-domain.com
VITE_WS_URL=wss://your-domain.com:8282
```

#### 2.3 构建生产版本
```bash
npm run build
```

#### 2.4 部署静态文件
将 `dist` 目录的内容部署到Web服务器:

**Nginx配置示例:**
```nginx
server {
    listen 80;
    server_name admin.your-domain.com;
    root /path/to/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://your-domain.com;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 初始化数据

### 创建管理员账户
```bash
php think user:create admin admin@example.com password123 admin
```

或直接在数据库中插入:
```sql
INSERT INTO users (username, email, password, role, status, created_at, updated_at) 
VALUES ('admin', 'admin@example.com', '$2y$10$...', 'admin', 1, NOW(), NOW());
```

## 配置说明

### JWT配置
- `JWT.SECRET`: JWT密钥,建议使用32位以上随机字符串
- `JWT.EXPIRE`: Token过期时间(秒),默认7200秒(2小时)

### 文件上传配置
- 上传目录: `backend/public/uploads`
- 支持格式: JPG, JPEG, PNG, GIF
- 最大大小: 5MB

### WebSocket配置
- 默认端口: 8282
- 配置文件: `backend/config/websocket.php`

## 安全建议

1. **修改默认密钥**
   - 修改 `.env` 中的 `JWT.SECRET`
   - 使用强密码保护数据库和Redis

2. **启用HTTPS**
   - 配置SSL证书
   - 强制HTTPS访问

3. **配置防火墙**
   - 只开放必要端口(80, 443, 8282)
   - 限制数据库和Redis的外部访问

4. **定期备份**
   - 备份数据库
   - 备份上传文件目录

5. **日志监控**
   - 定期检查错误日志
   - 监控异常访问

## 常见问题

### 1. 数据库连接失败
检查 `.env` 中的数据库配置是否正确,确保MySQL服务正常运行。

### 2. 文件上传失败
检查 `public/uploads` 目录权限是否为777。

### 3. WebSocket连接失败
- 检查8282端口是否开放
- 确认WebSocket服务器是否正常运行
- 检查防火墙设置

### 4. 前端API请求失败
- 检查 `.env.production` 中的API地址是否正确
- 确认后端CORS配置是否正确

## 性能优化

1. **启用OPcache**
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

2. **配置Redis缓存**
   - 缓存数据库查询结果
   - 缓存会话数据

3. **CDN加速**
   - 将静态资源部署到CDN
   - 加速图片和文件访问

4. **数据库优化**
   - 添加适当索引
   - 定期优化表

## 维护

### 日志位置
- 后端日志: `backend/runtime/log/`
- Nginx日志: `/var/log/nginx/`
- WebSocket日志: `/var/log/daoist-websocket.log`

### 清理缓存
```bash
php think clear
```

### 数据库备份
```bash
mysqldump -u root -p daoist_mall > backup_$(date +%Y%m%d).sql
```

## 技术支持

如有问题,请查看:
- API文档: `backend/docs/`
- 系统日志: `backend/runtime/log/`
