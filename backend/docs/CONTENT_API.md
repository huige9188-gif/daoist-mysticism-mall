# 内容管理API文档

本文档描述了道家玄学商城系统的内容管理API端点，包括视频管理、文章管理和风水师管理。

## 认证要求

所有内容管理API端点都需要管理员权限。请求时需要在请求头中包含有效的JWT令牌：

```
Authorization: Bearer <your_jwt_token>
```

## 通用响应格式

所有API响应遵循统一格式：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": { ... }
}
```

## 视频管理API

### 1. 获取视频列表

**端点:** `GET /api/videos`

**查询参数:**
- `page` (可选): 页码，默认为1
- `page_size` (可选): 每页数量，默认为10
- `enabled_only` (可选): 是否只获取启用的视频，默认为false

**响应示例:**
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "title": "道家养生视频",
            "description": "介绍道家养生方法",
            "video_url": "http://example.com/video1.mp4",
            "cover_image": "http://example.com/cover1.jpg",
            "status": 1,
            "created_at": "2024-01-01 10:00:00",
            "updated_at": "2024-01-01 10:00:00"
        }
    ]
}
```

**验证需求:** 6.6

---

### 2. 创建视频

**端点:** `POST /api/videos`

**请求体:**
```json
{
    "title": "视频标题",
    "description": "视频描述",
    "video_url": "http://example.com/video.mp4",
    "cover_image": "http://example.com/cover.jpg",
    "status": 1
}
```

**必填字段:**
- `title`: 视频标题（不能为空）
- `video_url`: 视频URL（不能为空）

**可选字段:**
- `description`: 视频描述
- `cover_image`: 封面图片URL
- `status`: 状态（1:启用 0:禁用），默认为1

**响应示例:**
```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 1,
        "title": "视频标题",
        "description": "视频描述",
        "video_url": "http://example.com/video.mp4",
        "cover_image": "http://example.com/cover.jpg",
        "status": 1,
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

**错误响应:**
```json
{
    "code": 400,
    "message": "视频标题不能为空",
    "data": null
}
```

**验证需求:** 6.1, 6.2, 6.3

---

### 3. 更新视频

**端点:** `PUT /api/videos/:id`

**路径参数:**
- `id`: 视频ID

**请求体:**
```json
{
    "title": "更新后的标题",
    "description": "更新后的描述"
}
```

**响应示例:**
```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 1,
        "title": "更新后的标题",
        "description": "更新后的描述",
        "video_url": "http://example.com/video.mp4",
        "cover_image": "http://example.com/cover.jpg",
        "status": 1,
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 11:00:00"
    }
}
```

**错误响应:**
```json
{
    "code": 404,
    "message": "视频不存在",
    "data": null
}
```

**验证需求:** 6.4

---

### 4. 删除视频

**端点:** `DELETE /api/videos/:id`

**路径参数:**
- `id`: 视频ID

**响应示例:**
```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

**注意:** 这是软删除操作，视频记录仍然存在于数据库中，但`deleted_at`字段会被设置。

**验证需求:** 6.5

---

### 5. 更新视频状态

**端点:** `PATCH /api/videos/:id/status`

**路径参数:**
- `id`: 视频ID

**请求体:**
```json
{
    "status": 1
}
```

**状态值:**
- `1`: 启用
- `0`: 禁用

**响应示例:**
```json
{
    "code": 200,
    "message": "状态更新成功",
    "data": {
        "id": 1,
        "title": "视频标题",
        "status": 1,
        ...
    }
}
```

**验证需求:** 6.6

---

## 文章管理API

### 1. 获取文章列表

**端点:** `GET /api/articles`

**查询参数:**
- `page` (可选): 页码，默认为1
- `page_size` (可选): 每页数量，默认为10
- `published_only` (可选): 是否只获取已发布的文章，默认为false

**响应示例:**
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "title": "道家文化介绍",
            "content": "文章内容...",
            "cover_image": "http://example.com/cover.jpg",
            "author": "张三",
            "status": "published",
            "created_at": "2024-01-01 10:00:00",
            "updated_at": "2024-01-01 10:00:00"
        }
    ]
}
```

**验证需求:** 7.6, 7.7

---

### 2. 创建文章

**端点:** `POST /api/articles`

**请求体:**
```json
{
    "title": "文章标题",
    "content": "文章内容",
    "cover_image": "http://example.com/cover.jpg",
    "author": "作者名",
    "status": "draft"
}
```

**必填字段:**
- `title`: 文章标题（不能为空）
- `content`: 文章内容（不能为空）

**可选字段:**
- `cover_image`: 封面图片URL
- `author`: 作者名
- `status`: 状态（draft:草稿 published:已发布），默认为draft

**响应示例:**
```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 1,
        "title": "文章标题",
        "content": "文章内容",
        "cover_image": "http://example.com/cover.jpg",
        "author": "作者名",
        "status": "draft",
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

**错误响应:**
```json
{
    "code": 400,
    "message": "文章标题不能为空",
    "data": null
}
```

**验证需求:** 7.1, 7.2, 7.3

---

### 3. 更新文章

**端点:** `PUT /api/articles/:id`

**路径参数:**
- `id`: 文章ID

**请求体:**
```json
{
    "title": "更新后的标题",
    "content": "更新后的内容"
}
```

**响应示例:**
```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 1,
        "title": "更新后的标题",
        "content": "更新后的内容",
        ...
    }
}
```

**验证需求:** 7.4

---

### 4. 删除文章

**端点:** `DELETE /api/articles/:id`

**路径参数:**
- `id`: 文章ID

**响应示例:**
```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

**注意:** 这是软删除操作，文章记录仍然存在于数据库中，但`deleted_at`字段会被设置。

**验证需求:** 7.5

---

### 5. 更新文章状态

**端点:** `PATCH /api/articles/:id/status`

**路径参数:**
- `id`: 文章ID

**请求体:**
```json
{
    "status": "published"
}
```

**状态值:**
- `draft`: 草稿（文章在前台不可见）
- `published`: 已发布（文章在前台可见）

**响应示例:**
```json
{
    "code": 200,
    "message": "状态更新成功",
    "data": {
        "id": 1,
        "title": "文章标题",
        "status": "published",
        ...
    }
}
```

**验证需求:** 7.6, 7.7

---

## 风水师管理API

### 1. 获取风水师列表

**端点:** `GET /api/feng-shui-masters`

**查询参数:**
- `page` (可选): 页码，默认为1
- `page_size` (可选): 每页数量，默认为10
- `enabled_only` (可选): 是否只获取启用的风水师，默认为false

**响应示例:**
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "name": "李大师",
            "bio": "资深风水师，从业20年",
            "specialty": "住宅风水、商业风水",
            "contact": "13800138000",
            "avatar": "http://example.com/avatar.jpg",
            "status": 1,
            "created_at": "2024-01-01 10:00:00",
            "updated_at": "2024-01-01 10:00:00"
        }
    ]
}
```

**验证需求:** 8.5

---

### 2. 创建风水师

**端点:** `POST /api/feng-shui-masters`

**请求体:**
```json
{
    "name": "李大师",
    "bio": "资深风水师，从业20年",
    "specialty": "住宅风水、商业风水",
    "contact": "13800138000",
    "avatar": "http://example.com/avatar.jpg",
    "status": 1
}
```

**必填字段:**
- `name`: 姓名（不能为空）

**可选字段:**
- `bio`: 简介
- `specialty`: 专长
- `contact`: 联系方式
- `avatar`: 头像URL
- `status`: 状态（1:启用 0:禁用），默认为1

**响应示例:**
```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 1,
        "name": "李大师",
        "bio": "资深风水师，从业20年",
        "specialty": "住宅风水、商业风水",
        "contact": "13800138000",
        "avatar": "http://example.com/avatar.jpg",
        "status": 1,
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

**错误响应:**
```json
{
    "code": 400,
    "message": "风水师姓名不能为空",
    "data": null
}
```

**验证需求:** 8.1, 8.2

---

### 3. 更新风水师

**端点:** `PUT /api/feng-shui-masters/:id`

**路径参数:**
- `id`: 风水师ID

**请求体:**
```json
{
    "name": "更新后的姓名",
    "bio": "更新后的简介"
}
```

**响应示例:**
```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 1,
        "name": "更新后的姓名",
        "bio": "更新后的简介",
        ...
    }
}
```

**验证需求:** 8.3

---

### 4. 删除风水师

**端点:** `DELETE /api/feng-shui-masters/:id`

**路径参数:**
- `id`: 风水师ID

**响应示例:**
```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

**注意:** 这是软删除操作，风水师记录仍然存在于数据库中，但`deleted_at`字段会被设置。

**验证需求:** 8.4

---

### 5. 更新风水师状态

**端点:** `PATCH /api/feng-shui-masters/:id/status`

**路径参数:**
- `id`: 风水师ID

**请求体:**
```json
{
    "status": 1
}
```

**状态值:**
- `1`: 启用（风水师在前台可见）
- `0`: 禁用（风水师在前台不可见）

**响应示例:**
```json
{
    "code": 200,
    "message": "状态更新成功",
    "data": {
        "id": 1,
        "name": "李大师",
        "status": 1,
        ...
    }
}
```

**验证需求:** 8.5

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误或验证失败 |
| 401 | 未授权访问（未登录或token无效） |
| 403 | 无权限访问（需要管理员权限） |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

## 使用示例

### 使用curl创建视频

```bash
curl -X POST http://localhost/api/videos \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "道家养生视频",
    "description": "介绍道家养生方法",
    "video_url": "http://example.com/video.mp4",
    "cover_image": "http://example.com/cover.jpg",
    "status": 1
  }'
```

### 使用curl发布文章

```bash
curl -X PATCH http://localhost/api/articles/1/status \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published"
  }'
```

### 使用curl获取风水师列表

```bash
curl -X GET "http://localhost/api/feng-shui-masters?page=1&page_size=10&enabled_only=true" \
  -H "Authorization: Bearer your_jwt_token"
```
