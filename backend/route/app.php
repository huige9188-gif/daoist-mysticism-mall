<?php

use think\facade\Route;

// API路由组
Route::group('api', function () {
    // 公开路由
    Route::post('login', 'api.Auth/login');
    
    // 需要认证的路由
    Route::group(function () {
        // 仪表盘
        Route::get('dashboard/stats', 'api.Dashboard/stats');
        
        // 用户管理
        Route::resource('users', 'api.User');
        Route::patch('users/:id/status', 'api.User/updateStatus');
        
        // 商品分类
        Route::resource('categories', 'api.Category');
        Route::patch('categories/:id/status', 'api.Category/updateStatus');
        
        // 商品管理
        Route::resource('products', 'api.Product');
        Route::patch('products/:id/status', 'api.Product/updateStatus');
        
        // 订单管理
        Route::resource('orders', 'api.Order');
        Route::post('orders/:id/ship', 'api.Order/ship');
        Route::post('orders/:id/cancel', 'api.Order/cancel');
        
        // 视频管理
        Route::resource('videos', 'api.Video');
        
        // 文章管理
        Route::resource('articles', 'api.Article');
        
        // 风水师管理
        Route::resource('feng-shui-masters', 'api.FengShuiMaster');
        
        // 支付配置
        Route::get('payment-configs', 'api.PaymentConfig/index');
        Route::post('payment-configs', 'api.PaymentConfig/save');
        
        // 支付
        Route::post('payments', 'api.Payment/create');
        
        // 客服聊天
        Route::get('chat/sessions', 'api.Chat/sessions');
        Route::get('chat/sessions/:id/messages', 'api.Chat/messages');
        Route::post('chat/sessions', 'api.Chat/createSession');
        Route::post('chat/sessions/:id/close', 'api.Chat/closeSession');
        
        // 文件上传
        Route::post('upload/image', 'api.Upload/image');
    })->middleware('auth');
    
    // 支付回调（不需要认证）
    Route::post('payments/callback', 'api.Payment/callback');
})->middleware('cors');
