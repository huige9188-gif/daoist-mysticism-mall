# 中间件文档

## JWT认证中间件

### 概述

本系统实现了基于JWT（JSON Web Token）的认证和授权机制，包含以下组件：

1. **Jwt工具类** (`app/common/Jwt.php`) - JWT令牌的生成和验证
2. **AuthMiddleware** (`app/middleware/AuthMiddleware.php`) - 认证中间件
3. **AdminMiddleware** (`app/middleware/AdminMiddleware.php`) - 管理员权限中间件

### 验证需求

- **需求 11.1**: 验证用户名和密码的正确性
- **需求 11.2**: 登录凭证正确时生成JWT令牌
- **需求 11.4**: 验证JWT令牌的有效性
- **需求 11.5**: 无效或过期令牌返回401错误
- **需求 11.6**: 验证Admin角色权限
- **需求 11.7**: 普通用户访问管理功能返回403错误

## 使用方法

### 1. JWT令牌生成

在用户登录成功后生成JWT令牌：

```php
use app\common\Jwt;

// 用户登录验证通过后
$payload = [
    'id' => $user->id,
    'username' => $user->username,
    'email' => $user->email,
    'role' => $user->role
];

$token = Jwt::generateToken($payload);

return json([
    'code' => 200,
    'message' => '登录成功',
    'data' => [
        'token' => $token,
        'user' => $payload
    ]
]);
```

### 2. 在路由中使用认证中间件

#### 保护需要登录的API端点

```php
// route/app.php

use think\facade\Route;

// 需要认证的路由
Route::group('api', function () {
    // 用户个人信息
    Route::get('user/profile', 'api.User/profile');
    
    // 订单相关
    Route::get('orders', 'api.Order/index');
    Route::post('orders', 'api.Order/create');
    
})->middleware('auth');
```

#### 保护需要管理员权限的API端点

```php
// 需要管理员权限的路由
Route::group('admin', function () {
    // 用户管理
    Route::get('users', 'admin.User/index');
    Route::post('users', 'admin.User/create');
    Route::put('users/:id', 'admin.User/update');
    Route::delete('users/:id', 'admin.User/delete');
    
    // 商品管理
    Route::get('products', 'admin.Product/index');
    Route::post('products', 'admin.Product/create');
    
})->middleware(['auth', 'admin']); // 先认证，再检查管理员权限
```

### 3. 在控制器中获取用户信息

认证中间件会将用户信息注入到请求对象中：

```php
namespace app\controller\api;

use app\BaseController;

class User extends BaseController
{
    public function profile()
    {
        // 获取当前登录用户信息
        $user = $this->request->user;
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }
}
```

### 4. 前端使用JWT令牌

#### 存储令牌

```javascript
// 登录成功后存储token
const response = await axios.post('/api/login', {
    username: 'user',
    password: 'password'
});

const token = response.data.data.token;
localStorage.setItem('token', token);
```

#### 在请求中携带令牌

```javascript
// 方式1: 使用Bearer格式（推荐）
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

// 方式2: 直接使用token
axios.defaults.headers.common['Authorization'] = token;

// 发送请求
const response = await axios.get('/api/user/profile');
```

## 配置

### JWT配置

在 `.env` 文件中配置JWT参数：

```ini
[JWT]
SECRET = your-secret-key-change-in-production
EXPIRE = 7200
```

- **SECRET**: JWT签名密钥（生产环境必须修改为强密钥）
- **EXPIRE**: 令牌过期时间（秒），默认7200秒（2小时）

### 中间件别名配置

在 `config/middleware.php` 中已配置中间件别名：

```php
return [
    'alias' => [
        'auth' => app\middleware\AuthMiddleware::class,
        'admin' => app\middleware\AdminMiddleware::class,
        'cors' => app\middleware\CorsMiddleware::class,
    ],
];
```

## 工作流程

### 认证流程

```
1. 用户登录 → 验证用户名密码
2. 验证成功 → 生成JWT令牌
3. 返回令牌给客户端
4. 客户端存储令牌
5. 后续请求携带令牌
6. AuthMiddleware验证令牌
7. 令牌有效 → 提取用户信息 → 注入请求 → 继续处理
8. 令牌无效 → 返回401错误
```

### 授权流程

```
1. 请求通过AuthMiddleware认证
2. AdminMiddleware检查用户角色
3. 角色为'admin' → 允许访问
4. 角色不是'admin' → 返回403错误
```

## 错误响应

### 401 未授权

```json
{
    "code": 401,
    "message": "未提供认证令牌",
    "data": null
}
```

```json
{
    "code": 401,
    "message": "认证令牌无效或已过期",
    "data": null
}
```

### 403 禁止访问

```json
{
    "code": 403,
    "message": "无权限访问，需要管理员权限",
    "data": null
}
```

## 测试

运行认证相关测试：

```bash
# 运行所有认证测试
vendor/bin/phpunit tests/Auth/

# 运行特定测试
vendor/bin/phpunit tests/Auth/JwtTest.php
vendor/bin/phpunit tests/Auth/AuthMiddlewareTest.php
vendor/bin/phpunit tests/Auth/AdminMiddlewareTest.php
```

## 安全建议

1. **生产环境必须修改JWT密钥**：使用强随机密钥，不要使用默认值
2. **使用HTTPS**：JWT令牌应该通过HTTPS传输
3. **合理设置过期时间**：根据业务需求设置合适的令牌过期时间
4. **令牌刷新机制**：考虑实现令牌刷新机制，避免频繁登录
5. **令牌撤销**：对于敏感操作，考虑实现令牌黑名单机制

## 扩展功能

### 令牌刷新

可以实现令牌刷新端点：

```php
public function refreshToken()
{
    $user = $this->request->user;
    
    // 生成新令牌
    $newToken = Jwt::generateToken([
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);
    
    return json([
        'code' => 200,
        'message' => '令牌刷新成功',
        'data' => ['token' => $newToken]
    ]);
}
```

### 多角色权限

可以扩展AdminMiddleware支持多角色：

```php
class RoleMiddleware
{
    private array $allowedRoles;
    
    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }
    
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user ?? null;
        $role = $user['role'] ?? 'user';
        
        if (!in_array($role, $this->allowedRoles)) {
            return json(['code' => 403, 'message' => '无权限访问'], 403);
        }
        
        return $next($request);
    }
}
```

## 相关文件

- `app/common/Jwt.php` - JWT工具类
- `app/middleware/AuthMiddleware.php` - 认证中间件
- `app/middleware/AdminMiddleware.php` - 管理员权限中间件
- `config/middleware.php` - 中间件配置
- `tests/Auth/JwtTest.php` - JWT工具类测试
- `tests/Auth/AuthMiddlewareTest.php` - 认证中间件测试
- `tests/Auth/AdminMiddlewareTest.php` - 管理员权限中间件测试
