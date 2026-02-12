# 角色权限验证使用指南

## 概述

本系统实现了基于角色的权限验证机制，支持Admin和User两种角色。通过中间件实现权限控制，确保只有具有相应权限的用户才能访问特定的API端点。

## 中间件说明

### 1. AuthMiddleware (认证中间件)

**功能**: 验证JWT令牌，提取用户信息

**使用场景**: 所有需要用户登录的API端点

**验证需求**: 11.1, 11.2, 11.4

**行为**:
- 从请求头获取JWT令牌
- 验证令牌有效性
- 将用户信息注入到请求对象中
- 如果令牌无效或过期，返回401错误

### 2. AdminMiddleware (管理员权限中间件)

**功能**: 验证用户是否具有管理员角色

**使用场景**: 所有需要管理员权限的API端点

**验证需求**: 11.6, 11.7

**行为**:
- 检查用户角色是否为'admin'
- 如果是admin角色，允许访问
- 如果是user角色或其他角色，返回403错误
- 如果没有用户信息，返回401错误

**注意**: AdminMiddleware必须在AuthMiddleware之后使用

## 使用方法

### 在路由中使用

#### 1. 仅需要认证的路由

```php
// 普通用户和管理员都可以访问
Route::group('api', function () {
    Route::get('me', 'api.Auth/me');
    Route::get('profile', 'api.User/profile');
})->middleware('auth');
```

#### 2. 需要管理员权限的路由

```php
// 只有管理员可以访问
Route::group('api/admin', function () {
    // 用户管理
    Route::get('users', 'api.User/index');
    Route::post('users', 'api.User/create');
    Route::put('users/:id', 'api.User/update');
    Route::delete('users/:id', 'api.User/delete');
    
    // 商品管理
    Route::get('products', 'api.Product/index');
    Route::post('products', 'api.Product/create');
    
    // 订单管理
    Route::get('orders', 'api.Order/index');
    Route::post('orders/:id/ship', 'api.Order/ship');
})->middleware(['auth', 'admin']);
```

### 在控制器中获取用户信息

```php
class UserController
{
    public function profile(Request $request)
    {
        // 获取当前登录用户信息（由AuthMiddleware注入）
        $user = $request->user;
        
        return json([
            'code' => 200,
            'message' => '成功',
            'data' => $user
        ]);
    }
}
```

## 角色说明

### Admin角色

**权限**: 可以访问所有管理功能

**功能**:
- 用户管理（创建、编辑、删除、启用/禁用用户）
- 商品管理（创建、编辑、删除、上下架商品）
- 商品分类管理
- 订单管理（查看、发货、取消订单）
- 视频管理
- 资讯管理
- 风水师管理
- 支付配置管理
- 客服管理
- 仪表盘统计

### User角色

**权限**: 只能访问普通用户功能

**功能**:
- 查看个人信息
- 浏览商品
- 创建订单
- 查看自己的订单
- 发起客服聊天
- 查看视频和资讯

## 错误响应

### 401 未授权访问

**场景**:
- 未提供JWT令牌
- JWT令牌无效或过期
- AdminMiddleware中没有用户信息

**响应格式**:
```json
{
    "code": 401,
    "message": "未授权访问",
    "data": null
}
```

### 403 无权限访问

**场景**:
- 普通用户尝试访问管理员功能
- 用户角色不是'admin'

**响应格式**:
```json
{
    "code": 403,
    "message": "无权限访问，需要管理员权限",
    "data": null
}
```

## 测试

### 单元测试

AdminMiddlewareTest.php 包含以下测试用例：

1. **testAdminRoleAllowsAccess**: 验证Admin角色允许访问
2. **testUserRoleReturns403**: 验证普通用户返回403错误
3. **testUnauthenticatedUserReturns401**: 验证未认证用户返回401错误
4. **testMissingRoleDefaultsToUser**: 验证缺少role字段的用户被视为普通用户
5. **testOtherRolesAreDenied**: 验证其他角色值被拒绝
6. **testRoleCheckIsCaseSensitive**: 验证大小写敏感的角色检查

### 运行测试

```bash
composer test tests/Auth/AdminMiddlewareTest.php
```

## 配置

中间件别名在 `config/middleware.php` 中配置：

```php
return [
    'alias' => [
        'auth' => app\middleware\AuthMiddleware::class,
        'admin' => app\middleware\AdminMiddleware::class,
        'cors' => app\middleware\CorsMiddleware::class,
    ],
];
```

## 最佳实践

1. **始终先使用auth中间件**: AdminMiddleware依赖AuthMiddleware注入的用户信息
2. **使用中间件数组**: `->middleware(['auth', 'admin'])`
3. **按功能分组路由**: 将需要相同权限的路由放在同一个路由组中
4. **明确的路由前缀**: 管理员路由使用 `/api/admin` 前缀，便于识别和管理
5. **角色检查严格**: 角色名称区分大小写，必须精确匹配'admin'

## 示例：完整的管理员路由配置

```php
// backend/route/api.php

// 公开路由（无需认证）
Route::group('api', function () {
    Route::post('login', 'api.Auth/login');
    Route::post('register', 'api.Auth/register');
});

// 需要认证的路由（普通用户和管理员都可以访问）
Route::group('api', function () {
    Route::get('me', 'api.Auth/me');
    Route::post('logout', 'api.Auth/logout');
    Route::get('products', 'api.Product/list');
    Route::get('products/:id', 'api.Product/detail');
})->middleware('auth');

// 需要管理员权限的路由（只有管理员可以访问）
Route::group('api/admin', function () {
    // 仪表盘
    Route::get('dashboard/stats', 'api.Dashboard/stats');
    
    // 用户管理
    Route::get('users', 'api.User/index');
    Route::post('users', 'api.User/create');
    Route::put('users/:id', 'api.User/update');
    Route::delete('users/:id', 'api.User/delete');
    Route::patch('users/:id/status', 'api.User/updateStatus');
    
    // 商品管理
    Route::get('products', 'api.Product/index');
    Route::post('products', 'api.Product/create');
    Route::put('products/:id', 'api.Product/update');
    Route::delete('products/:id', 'api.Product/delete');
    Route::patch('products/:id/status', 'api.Product/updateStatus');
    
    // 订单管理
    Route::get('orders', 'api.Order/index');
    Route::get('orders/:id', 'api.Order/detail');
    Route::post('orders/:id/ship', 'api.Order/ship');
    Route::post('orders/:id/cancel', 'api.Order/cancel');
})->middleware(['auth', 'admin']);
```

## 总结

角色权限验证系统通过两个中间件实现：
- **AuthMiddleware**: 验证用户身份
- **AdminMiddleware**: 验证管理员权限

这种设计确保了：
- ✅ Admin角色可以访问所有管理功能（需求11.6）
- ✅ User角色访问管理功能时返回403错误（需求11.7）
- ✅ 未认证用户返回401错误
- ✅ 权限检查严格且安全
- ✅ 易于使用和维护
