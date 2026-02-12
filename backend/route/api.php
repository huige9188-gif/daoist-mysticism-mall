<?php

use think\facade\Route;

// 认证相关路由（无需认证）
Route::group('api', function () {
    // 用户登录
    Route::post('login', 'api.Auth/login');
    
    // 用户登出（虽然不需要认证，但通常会携带token）
    Route::post('logout', 'api.Auth/logout');
});

// 需要认证的路由
Route::group('api', function () {
    // 获取当前用户信息
    Route::get('me', 'api.Auth/me');
    
})->middleware('auth');

// 需要管理员权限的路由
Route::group('api', function () {
    // 仪表盘统计路由
    Route::get('dashboard/stats', 'api.Dashboard/stats');
    
    // 用户管理路由
    Route::get('users', 'api.User/index');
    Route::post('users', 'api.User/create');
    Route::put('users/:id', 'api.User/update');
    Route::delete('users/:id', 'api.User/delete');
    Route::patch('users/:id/status', 'api.User/updateStatus');
    
    // 商品分类管理路由
    Route::get('categories', 'api.Category/index');
    Route::post('categories', 'api.Category/create');
    Route::put('categories/:id', 'api.Category/update');
    Route::delete('categories/:id', 'api.Category/delete');
    Route::patch('categories/:id/status', 'api.Category/updateStatus');
    
    // 商品管理路由
    Route::get('products', 'api.Product/index');
    Route::post('products', 'api.Product/create');
    Route::put('products/:id', 'api.Product/update');
    Route::delete('products/:id', 'api.Product/delete');
    Route::patch('products/:id/status', 'api.Product/updateStatus');
    
    // 订单管理路由
    Route::get('orders', 'api.Order/index');
    Route::get('orders/:id', 'api.Order/read');
    Route::post('orders/:id/ship', 'api.Order/ship');
    Route::post('orders/:id/cancel', 'api.Order/cancel');
    
    // 文件上传路由
    Route::post('upload/image', 'api.Upload/image');
    
    // 视频管理路由
    Route::get('videos', 'api.Video/index');
    Route::post('videos', 'api.Video/create');
    Route::put('videos/:id', 'api.Video/update');
    Route::delete('videos/:id', 'api.Video/delete');
    Route::patch('videos/:id/status', 'api.Video/updateStatus');
    
    // 文章管理路由
    Route::get('articles', 'api.Article/index');
    Route::post('articles', 'api.Article/create');
    Route::put('articles/:id', 'api.Article/update');
    Route::delete('articles/:id', 'api.Article/delete');
    Route::patch('articles/:id/status', 'api.Article/updateStatus');
    
    // 风水师管理路由
    Route::get('feng-shui-masters', 'api.FengShuiMaster/index');
    Route::post('feng-shui-masters', 'api.FengShuiMaster/create');
    Route::put('feng-shui-masters/:id', 'api.FengShuiMaster/update');
    Route::delete('feng-shui-masters/:id', 'api.FengShuiMaster/delete');
    Route::patch('feng-shui-masters/:id/status', 'api.FengShuiMaster/updateStatus');
    
})->middleware(['auth', 'admin']);

// 用户订单路由（需要认证但不需要管理员权限）
Route::group('api', function () {
    // 创建订单（普通用户也可以创建）
    Route::post('orders', 'api.Order/create');
    
    // 创建支付（普通用户也可以创建）
    Route::post('payments', 'api.Payment/createPayment');
    
    // 获取可用的支付方式
    Route::get('payments/gateways', 'api.Payment/getAvailableGateways');
    
})->middleware('auth');

// 支付回调路由（无需认证，由支付网关调用）
Route::group('api', function () {
    // 支付回调
    Route::post('payments/callback', 'api.Payment/handleCallback');
});

// 支付配置管理路由（需要管理员权限）
Route::group('api', function () {
    // 获取支付配置列表
    Route::get('payment-configs', 'api.Payment/getConfigs');
    
    // 保存支付配置
    Route::post('payment-configs', 'api.Payment/saveConfig');
    
})->middleware(['auth', 'admin']);

// 客服管理路由（需要认证）
Route::group('api', function () {
    // 获取会话列表（管理员查看所有会话）
    Route::get('chat/sessions', 'api.Chat/sessions')->middleware('admin');
    
    // 获取会话聊天记录
    Route::get('chat/sessions/:id/messages', 'api.Chat/messages');
    
    // 创建会话（普通用户也可以创建）
    Route::post('chat/sessions', 'api.Chat/createSession');
    
    // 结束会话（管理员或会话所有者）
    Route::post('chat/sessions/:id/close', 'api.Chat/closeSession');
    
})->middleware('auth');
