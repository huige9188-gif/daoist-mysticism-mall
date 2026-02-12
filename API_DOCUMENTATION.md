# 道家玄学商城系统 - API文档

## 基础信息

- 基础URL: `http://your-domain.com/api`
- 认证方式: JWT Bearer Token
- 响应格式: JSON

## 统一响应格式

### 成功响应
```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

### 失败响应
```json
{
  "code": 400,
  "message": "错误信息",
  "data": null
}
```

## 认证接口

### 用户登录
- **URL**: `/login`
- **方法**: `POST`
- **认证**: 无需认证

**请求参数:**
```json
{
  "username": "admin",
  "password": "password123"
}
```

**响应:**
```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

## 用户管理接口

### 获取用户列表
- **URL**: `/users`
- **方法**: `GET`
- **认证**: 需要Admin权限

**查询参数:**
- `page`: 页码(默认1)
- `pageSize`: 每页数量(默认10)
- `search`: 搜索关键词(可选)

### 创建用户
- **URL**: `/users`
- **方法**: `POST`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "password123",
  "phone": "13800138000",
  "role": "user"
}
```

### 更新用户
- **URL**: `/users/:id`
- **方法**: `PUT`
- **认证**: 需要Admin权限

### 删除用户
- **URL**: `/users/:id`
- **方法**: `DELETE`
- **认证**: 需要Admin权限

### 更新用户状态
- **URL**: `/users/:id/status`
- **方法**: `PATCH`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "status": 1
}
```

## 商品分类接口

### 获取分类列表
- **URL**: `/categories`
- **方法**: `GET`
- **认证**: 无需认证

### 创建分类
- **URL**: `/categories`
- **方法**: `POST`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "name": "分类名称",
  "description": "分类描述",
  "sort": 1
}
```

### 更新分类
- **URL**: `/categories/:id`
- **方法**: `PUT`
- **认证**: 需要Admin权限

### 删除分类
- **URL**: `/categories/:id`
- **方法**: `DELETE`
- **认证**: 需要Admin权限

## 商品管理接口

### 获取商品列表
- **URL**: `/products`
- **方法**: `GET`
- **认证**: 无需认证

**查询参数:**
- `page`: 页码
- `pageSize`: 每页数量
- `category_id`: 分类ID(可选)
- `search`: 搜索关键词(可选)
- `status`: 状态筛选(可选)

### 创建商品
- **URL**: `/products`
- **方法**: `POST`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "name": "商品名称",
  "description": "商品描述",
  "price": 99.99,
  "stock": 100,
  "category_id": 1,
  "images": ["image1.jpg", "image2.jpg"]
}
```

### 更新商品
- **URL**: `/products/:id`
- **方法**: `PUT`
- **认证**: 需要Admin权限

### 删除商品
- **URL**: `/products/:id`
- **方法**: `DELETE`
- **认证**: 需要Admin权限

### 商品上下架
- **URL**: `/products/:id/status`
- **方法**: `PATCH`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "status": "on_sale"
}
```

## 订单管理接口

### 创建订单
- **URL**: `/orders`
- **方法**: `POST`
- **认证**: 需要登录

**请求参数:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "address": {
    "name": "收货人",
    "phone": "13800138000",
    "province": "广东省",
    "city": "深圳市",
    "district": "南山区",
    "detail": "详细地址"
  }
}
```

### 获取订单列表
- **URL**: `/orders`
- **方法**: `GET`
- **认证**: 需要登录

**查询参数:**
- `page`: 页码
- `pageSize`: 每页数量
- `status`: 状态筛选(可选)
- `search`: 搜索关键词(可选)

### 获取订单详情
- **URL**: `/orders/:id`
- **方法**: `GET`
- **认证**: 需要登录

### 订单发货
- **URL**: `/orders/:id/ship`
- **方法**: `POST`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "logistics_company": "顺丰速运",
  "logistics_number": "SF1234567890"
}
```

### 取消订单
- **URL**: `/orders/:id/cancel`
- **方法**: `POST`
- **认证**: 需要登录

## 支付接口

### 获取支付配置
- **URL**: `/payment-configs`
- **方法**: `GET`
- **认证**: 需要Admin权限

### 保存支付配置
- **URL**: `/payment-configs`
- **方法**: `POST`
- **认证**: 需要Admin权限

**请求参数:**
```json
{
  "type": "alipay",
  "config": {
    "app_id": "your_app_id",
    "private_key": "your_private_key",
    "public_key": "alipay_public_key"
  },
  "status": 1
}
```

### 创建支付
- **URL**: `/payments`
- **方法**: `POST`
- **认证**: 需要登录

**请求参数:**
```json
{
  "order_id": 1,
  "payment_type": "alipay"
}
```

### 支付回调
- **URL**: `/payments/callback`
- **方法**: `POST`
- **认证**: 无需认证

## 客服聊天接口

### 创建会话
- **URL**: `/chat/sessions`
- **方法**: `POST`
- **认证**: 需要登录

### 获取会话列表
- **URL**: `/chat/sessions`
- **方法**: `GET`
- **认证**: 需要Admin权限

### 获取聊天记录
- **URL**: `/chat/sessions/:id/messages`
- **方法**: `GET`
- **认证**: 需要登录

### 结束会话
- **URL**: `/chat/sessions/:id/close`
- **方法**: `POST`
- **认证**: 需要登录

### WebSocket连接
- **URL**: `ws://your-domain.com:8282/ws/chat?session_id=1`
- **协议**: WebSocket

**发送消息格式:**
```json
{
  "type": "message",
  "content": "消息内容"
}
```

**接收消息格式:**
```json
{
  "type": "message",
  "message": {
    "id": 1,
    "session_id": 1,
    "sender_type": "user",
    "content": "消息内容",
    "created_at": "2026-02-12 10:00:00"
  }
}
```

## 内容管理接口

### 视频管理
- **获取列表**: `GET /videos`
- **创建**: `POST /videos` (Admin)
- **更新**: `PUT /videos/:id` (Admin)
- **删除**: `DELETE /videos/:id` (Admin)

### 文章管理
- **获取列表**: `GET /articles`
- **创建**: `POST /articles` (Admin)
- **更新**: `PUT /articles/:id` (Admin)
- **删除**: `DELETE /articles/:id` (Admin)

### 风水师管理
- **获取列表**: `GET /feng-shui-masters`
- **创建**: `POST /feng-shui-masters` (Admin)
- **更新**: `PUT /feng-shui-masters/:id` (Admin)
- **删除**: `DELETE /feng-shui-masters/:id` (Admin)

## 文件上传接口

### 上传图片
- **URL**: `/upload/image`
- **方法**: `POST`
- **认证**: 需要登录
- **Content-Type**: `multipart/form-data`

**请求参数:**
- `file`: 图片文件

**响应:**
```json
{
  "code": 200,
  "message": "上传成功",
  "data": {
    "url": "/uploads/2026/02/12/image.jpg"
  }
}
```

**限制:**
- 支持格式: JPG, JPEG, PNG, GIF
- 最大大小: 5MB

## 仪表盘接口

### 获取统计数据
- **URL**: `/dashboard/stats`
- **方法**: `GET`
- **认证**: 需要Admin权限

**响应:**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "total_users": 100,
    "total_products": 50,
    "total_orders": 200,
    "total_revenue": 10000.00,
    "order_stats": {
      "pending": 10,
      "paid": 50,
      "shipped": 30,
      "completed": 100,
      "cancelled": 10
    },
    "recent_orders": []
  }
}
```

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权(未登录或token无效) |
| 403 | 禁止访问(权限不足) |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

## 认证说明

大部分接口需要在请求头中携带JWT Token:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

Token在登录成功后获取,有效期为2小时。

## 权限说明

- **Admin**: 管理员,拥有所有权限
- **User**: 普通用户,只能访问自己的数据

## 更多文档

详细的API文档请查看 `backend/docs/` 目录下的各个模块文档。
