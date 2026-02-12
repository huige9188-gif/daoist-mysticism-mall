# 文件上传API文档

## 概述

文件上传API提供图片文件上传功能，支持商品图片、文章封面、用户头像等媒体资源的上传。系统会自动验证文件类型和大小，并按日期组织存储目录。

**验证需求**: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6

## 认证

所有文件上传API端点都需要：
1. JWT令牌认证（`auth`中间件）
2. 管理员角色权限（`admin`中间件）

请求头示例：
```
Authorization: Bearer <your-jwt-token>
```

## API端点

### 1. 上传图片文件

上传图片文件到服务器，支持jpg、jpeg、png、gif格式，最大5MB。

**端点**: `POST /api/upload/image`

**权限**: 管理员

**请求类型**: `multipart/form-data`

**请求参数**:
- `file` (必填): 图片文件

**文件限制**:
- **支持格式**: jpg, jpeg, png, gif
- **最大大小**: 5MB (5,242,880 字节)
- **文件命名**: 系统自动生成唯一文件名
- **存储路径**: uploads/YYYY/MM/DD/

**请求示例**:
```http
POST /api/upload/image
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: multipart/form-data

file: [binary data]
```

**成功响应** (200 OK):
```json
{
  "code": 200,
  "message": "上传成功",
  "data": {
    "url": "http://example.com/uploads/2024/01/15/1705305600_1705305600123456_a1b2c3d4e5f6g7h8.png",
    "path": "uploads/2024/01/15/1705305600_1705305600123456_a1b2c3d4e5f6g7h8.png"
  }
}
```

**响应字段说明**:
- `url`: 文件的完整访问URL，可直接用于前端显示
- `path`: 文件的相对路径，用于数据库存储

**错误响应**:

**1. 未选择文件** (400 Bad Request):
```json
{
  "code": 400,
  "message": "请选择要上传的文件",
  "data": null
}
```

**2. 不支持的文件类型** (400 Bad Request):
```json
{
  "code": 400,
  "message": "不支持的文件类型，仅支持jpg、jpeg、png、gif格式",
  "data": null
}
```

**3. 文件大小超过限制** (400 Bad Request):
```json
{
  "code": 400,
  "message": "文件大小超过限制，最大支持5MB",
  "data": null
}
```

**4. 未授权访问** (401 Unauthorized):
```json
{
  "code": 401,
  "message": "未授权访问",
  "data": null
}
```

**5. 无权限访问** (403 Forbidden):
```json
{
  "code": 403,
  "message": "无权限访问",
  "data": null
}
```

**6. 服务器错误** (500 Internal Server Error):
```json
{
  "code": 500,
  "message": "上传失败: [错误详情]",
  "data": null
}
```

**验证需求**: 15.1, 15.2, 15.3, 15.4

---

## 使用示例

### JavaScript (Axios)

```javascript
// 上传图片文件
async function uploadImage(file) {
  const formData = new FormData();
  formData.append('file', file);
  
  try {
    const response = await axios.post('/api/upload/image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${token}`
      }
    });
    
    console.log('上传成功:', response.data.data.url);
    return response.data.data;
  } catch (error) {
    console.error('上传失败:', error.response.data.message);
    throw error;
  }
}

// 使用示例
const fileInput = document.querySelector('input[type="file"]');
fileInput.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  
  // 客户端验证
  if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(file.type)) {
    alert('仅支持jpg、jpeg、png、gif格式');
    return;
  }
  
  if (file.size > 5 * 1024 * 1024) {
    alert('文件大小不能超过5MB');
    return;
  }
  
  try {
    const result = await uploadImage(file);
    console.log('文件URL:', result.url);
  } catch (error) {
    alert('上传失败');
  }
});
```

### Vue.js (Element UI)

```vue
<template>
  <el-upload
    action="/api/upload/image"
    :headers="uploadHeaders"
    :before-upload="beforeUpload"
    :on-success="handleSuccess"
    :on-error="handleError"
    :show-file-list="false"
  >
    <el-button size="small" type="primary">点击上传</el-button>
    <div slot="tip" class="el-upload__tip">
      只能上传jpg/jpeg/png/gif文件，且不超过5MB
    </div>
  </el-upload>
</template>

<script>
export default {
  data() {
    return {
      uploadHeaders: {
        Authorization: `Bearer ${this.$store.state.token}`
      }
    }
  },
  methods: {
    beforeUpload(file) {
      const isImage = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(file.type);
      const isLt5M = file.size / 1024 / 1024 < 5;
      
      if (!isImage) {
        this.$message.error('仅支持jpg、jpeg、png、gif格式');
        return false;
      }
      if (!isLt5M) {
        this.$message.error('文件大小不能超过5MB');
        return false;
      }
      return true;
    },
    handleSuccess(response, file, fileList) {
      if (response.code === 200) {
        this.$message.success('上传成功');
        console.log('文件URL:', response.data.url);
        // 使用 response.data.url 更新表单或显示图片
      } else {
        this.$message.error(response.message);
      }
    },
    handleError(err, file, fileList) {
      this.$message.error('上传失败');
      console.error(err);
    }
  }
}
</script>
```

### PHP (cURL)

```php
<?php

function uploadImage($filePath, $token) {
    $url = 'http://example.com/api/upload/image';
    
    $file = new CURLFile($filePath);
    $data = ['file' => $file];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && $result['code'] === 200) {
        return $result['data'];
    } else {
        throw new Exception($result['message']);
    }
}

// 使用示例
try {
    $token = 'your-jwt-token';
    $result = uploadImage('/path/to/image.jpg', $token);
    echo "上传成功，URL: " . $result['url'] . "\n";
} catch (Exception $e) {
    echo "上传失败: " . $e->getMessage() . "\n";
}
```

---

## 文件存储结构

上传的文件按日期组织存储，目录结构如下：

```
public/
└── uploads/
    └── 2024/
        └── 01/
            └── 15/
                ├── 1705305600_1705305600123456_a1b2c3d4e5f6g7h8.png
                ├── 1705305601_1705305601234567_b2c3d4e5f6g7h8i9.jpg
                └── ...
```

**文件命名规则**:
- 格式: `{timestamp}_{microtime}_{random}.{extension}`
- `timestamp`: Unix时间戳（秒）
- `microtime`: 微秒时间戳（去除小数点）
- `random`: 16位随机十六进制字符串
- `extension`: 原始文件扩展名

这种命名方式确保：
1. 文件名唯一性（验证需求 15.5）
2. 按时间排序
3. 避免文件名冲突
4. 便于追踪和管理

**目录组织**:
- 按年/月/日三级目录组织（验证需求 15.6）
- 便于文件管理和备份
- 避免单个目录文件过多

---

## 注意事项

1. **文件大小限制**: 
   - 客户端应在上传前进行文件大小验证，避免无效请求
   - 服务器端会严格验证文件大小，超过5MB将被拒绝

2. **文件类型验证**:
   - 仅支持图片格式：jpg, jpeg, png, gif
   - 服务器端通过文件扩展名验证类型
   - 建议客户端也进行类型验证，提升用户体验

3. **权限控制**:
   - 仅管理员可以上传文件
   - 普通用户上传请求将返回403错误

4. **文件访问**:
   - 上传成功后返回的URL可直接访问
   - 文件存储在public目录下，无需额外配置

5. **错误处理**:
   - 客户端应妥善处理各种错误情况
   - 建议显示友好的错误提示给用户

6. **性能优化**:
   - 建议在客户端进行图片压缩后再上传
   - 可以使用图片裁剪工具优化图片尺寸

---

## 相关需求

- **需求 15.1**: 文件类型验证（jpg、jpeg、png、gif）
- **需求 15.2**: 文件大小验证（最大5MB）
- **需求 15.3**: 上传成功返回文件URL
- **需求 15.4**: 验证失败返回错误信息
- **需求 15.5**: 生成唯一文件名
- **需求 15.6**: 按日期组织目录结构
