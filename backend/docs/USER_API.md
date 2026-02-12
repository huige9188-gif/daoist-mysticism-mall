# 用户管理API文档

## 概述

用户管理API提供了完整的用户CRUD操作，包括创建、读取、更新、删除和状态管理功能。所有端点都需要管理员权限。

**验证需求**: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6

## 认证

所有用户管理API端点都需要：
1. JWT令牌认证（`auth`中间件）
2. 管理员角色权限（`admin`中间件）

请求头示例：
```
Authorization: Bearer <your-jwt-token>
```

## API端点

### 1. 获取用户列表

获取系统中所有用户的分页列表，支持搜索功能。

**端点**: `GET /api/users`

**权限**: 管理员

**查询参数**:
- `page` (可选): 页码，默认为1
- `page_size` (可选): 每页数量，默认为10
- `search` (可选): 搜索关键词，支持按用户名、邮箱或手机号模糊搜索

**请求示例**:
```http
GET /api/users?page=1&page_size=10&search=test
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**成功响应** (200 OK):
```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "total": 50,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5,
    "data": [
      {
        "id": 1,
        "username": "testuser",
        "email": "test@example.com",
        "phone": "13800138000",
        "role": "user",
        "status": 1,
        "created_at": "2024-01-01 12:00:00",
        "updated_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

**验证需求**: 2.1, 2.6

---

### 2. 创建用户

创建新用户账号。

**端点**: `POST /api/users`

**权限**: 管理员

**请求体**:
```json
{
  "username": "newuser",
  "password": "password123",
  "email": "newuser@example.com",
  "phone": "13900139000",
  "role": "user",
  "status": 1
}
```

**字段说明**:
- `username` (必填): 用户名，必须唯一
- `password` (必填): 密码，将自动加密
- `email` (必填): 邮箱地址，必须符合邮箱格式且唯一
- `phone` (可选): 手机号码
- `role` (可选): 角色，可选值：`user`（默认）、`admin`
- `status` (可选): 状态，1=启用（默认），0=禁用

**成功响应** (200 OK):
```json
{
  "code": 200,
  "message": "创建成功",
  "data": {
    "id": 2,
    "username": "newuser",
    "email": "newuser@example.com",
    "phone": "13900139000",
    "role": "user",
    "status": 1,
    "created_at": "2024-01-01 12:00:00",
    "updated_at": "2024-01-01 12:00:00"
  }
}
```

**错误响应**:

用户名已存在 (400 Bad Request):
```json
{
  "code": 400,
  "message": "用户名已存在",
  "data": null
}
```

邮箱格式错误 (400 Bad Request):
```json
{
  "code": 400,
  "message": "邮箱格式不正确",
  "data": null
}
```

**验证需求**: 2.2

---

### 3. 更新用户

更新指定用户的信息。

**端点**: `PUT /api/users/:id`

**权限**: 管理员

**路径参数**:
- `id`: 用户ID

**请求体**:
```json
{
  "email": "updated@example.com",
  "phone": "13900139999",
  "status": 1
}
```

**注意**: 
- 可以只更新部分字段
- 密码字段不能通过此端点更新
- 如果更新用户名或邮箱，会自动检查唯一性

**成功响应** (200 OK):
```json
{
  "code": 200,
  "message": "更新成功",
  "data": {
    "id": 2,
    "username": "newuser",
    "email": "updated@example.com",
    "phone": "13900139999",
    "role": "user",
    "status": 1,
    "created_at": "2024-01-01 12:00:00",
    "updated_at": "2024-01-01 13:00:00"
  }
}
```

**错误响应**:

用户不存在 (404 Not Found):
```json
{
  "code": 404,
  "message": "用户不存在",
  "data": null
}
```

**验证需求**: 2.3

---

### 4. 删除用户

删除指定用户（软删除）。

**端点**: `DELETE /api/users/:id`

**权限**: 管理员

**路径参数**:
- `id`: 用户ID

**请求示例**:
```http
DELETE /api/users/2
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**成功响应** (200 OK):
```json
{
  "code": 200,
  "message": "删除成功",
  "data": null
}
```

**错误响应**:

用户不存在 (404 Not Found):
```json
{
  "code": 404,
  "message": "用户不存在",
  "data": null
}
```

**注意**: 
- 这是软删除操作，用户记录仍然保留在数据库中
- 删除后的用户`deleted_at`字段会被设置为当前时间
- 软删除的用户不会出现在正常的用户列表中

**验证需求**: 2.4

---

### 5. 更新用户状态

启用或禁用用户账号。

**端点**: `PATCH /api/users/:id/status`

**权限**: 管理员

**路径参数**:
- `id`: 用户ID

**请求体**:
```json
{
  "status": 0
}
```

**字段说明**:
- `status` (必填): 状态值，1=启用，0=禁用

**成功响应** (200 OK):
```json
{
  "code": 200,
  "message": "状态更新成功",
  "data": {
    "id": 2,
    "username": "newuser",
    "email": "newuser@example.com",
    "phone": "13900139000",
    "role": "user",
    "status": 0,
    "created_at": "2024-01-01 12:00:00",
    "updated_at": "2024-01-01 14:00:00"
  }
}
```

**错误响应**:

状态值无效 (400 Bad Request):
```json
{
  "code": 400,
  "message": "状态值无效",
  "data": null
}
```

用户不存在 (404 Not Found):
```json
{
  "code": 404,
  "message": "用户不存在",
  "data": null
}
```

**注意**: 
- 禁用的用户无法登录系统
- 状态值只能是0或1

**验证需求**: 2.5

---

## 错误码说明

| HTTP状态码 | 说明 |
|-----------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误或验证失败 |
| 401 | 未认证或令牌无效 |
| 403 | 无权限访问（非管理员） |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

## 使用示例

### cURL示例

```bash
# 1. 登录获取token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# 2. 获取用户列表
curl -X GET "http://localhost:8000/api/users?page=1&page_size=10" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 3. 创建用户
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newuser",
    "password": "password123",
    "email": "newuser@example.com",
    "phone": "13900139000",
    "role": "user",
    "status": 1
  }'

# 4. 更新用户
curl -X PUT http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "13900139999"
  }'

# 5. 更新用户状态
curl -X PATCH http://localhost:8000/api/users/2/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": 0
  }'

# 6. 删除用户
curl -X DELETE http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### JavaScript (Axios) 示例

```javascript
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8000/api';
const token = 'YOUR_JWT_TOKEN';

// 配置axios实例
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// 获取用户列表
async function getUserList(page = 1, pageSize = 10, search = '') {
  const response = await api.get('/users', {
    params: { page, page_size: pageSize, search }
  });
  return response.data;
}

// 创建用户
async function createUser(userData) {
  const response = await api.post('/users', userData);
  return response.data;
}

// 更新用户
async function updateUser(id, userData) {
  const response = await api.put(`/users/${id}`, userData);
  return response.data;
}

// 删除用户
async function deleteUser(id) {
  const response = await api.delete(`/users/${id}`);
  return response.data;
}

// 更新用户状态
async function updateUserStatus(id, status) {
  const response = await api.patch(`/users/${id}/status`, { status });
  return response.data;
}
```

## 测试

运行用户管理API测试：

```bash
# 验证API结构
php backend/tests/User/verify-api-structure.php

# 运行完整测试（需要配置数据库）
composer test tests/User/UserApiTest.php
```

## 相关文件

- 控制器: `backend/app/controller/api/User.php`
- 服务类: `backend/app/service/UserService.php`
- 模型: `backend/app/model/User.php`
- 路由: `backend/route/api.php`
- 测试: `backend/tests/User/UserApiTest.php`
