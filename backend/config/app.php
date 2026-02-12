<?php

return [
    // 应用调试模式
    'app_debug' => env('app.debug', false),
    
    // 应用Trace
    'app_trace' => env('app.trace', false),
    
    // 默认时区
    'default_timezone' => env('app.default_timezone', 'Asia/Shanghai'),
    
    // 异常页面的模板文件
    'exception_tmpl' => app()->getThinkPath() . 'tpl/think_exception.tpl',
    
    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    
    // 显示错误信息
    'show_error_msg' => false,
    
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => \app\ExceptionHandler::class,
];
