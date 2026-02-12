<?php

namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 统一异常处理器
 * 
 * 验证需求: 13.3, 13.5
 */
class ExceptionHandler extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        
        // 验证异常
        if ($e instanceof ValidateException) {
            return json([
                'code' => 400,
                'message' => $e->getError(),
                'data' => null
            ], 400);
        }

        // HTTP异常
        if ($e instanceof HttpException) {
            return json([
                'code' => $e->getStatusCode(),
                'message' => $e->getMessage(),
                'data' => null
            ], $e->getStatusCode());
        }

        // 模型未找到异常
        if ($e instanceof ModelNotFoundException || $e instanceof DataNotFoundException) {
            return json([
                'code' => 404,
                'message' => '资源不存在',
                'data' => null
            ], 404);
        }

        // 业务异常（通过异常消息判断）
        $message = $e->getMessage();
        
        // 认证错误
        if (strpos($message, '未授权') !== false || 
            strpos($message, '令牌') !== false ||
            strpos($message, 'token') !== false) {
            return json([
                'code' => 401,
                'message' => $message ?: '未授权访问',
                'data' => null
            ], 401);
        }
        
        // 授权错误
        if (strpos($message, '无权限') !== false || 
            strpos($message, '权限不足') !== false) {
            return json([
                'code' => 403,
                'message' => $message ?: '无权限访问',
                'data' => null
            ], 403);
        }
        
        // 资源不存在错误
        if (strpos($message, '不存在') !== false) {
            return json([
                'code' => 404,
                'message' => $message,
                'data' => null
            ], 404);
        }
        
        // 业务逻辑错误
        if (strpos($message, '无效') !== false || 
            strpos($message, '不能') !== false ||
            strpos($message, '已存在') !== false ||
            strpos($message, '不足') !== false ||
            strpos($message, '超过') !== false ||
            strpos($message, '失败') !== false) {
            return json([
                'code' => 400,
                'message' => $message,
                'data' => null
            ], 400);
        }

        // 其他错误统一处理为500
        // 在生产环境不显示具体错误信息
        if (env('app.debug', false)) {
            $errorMessage = $e->getMessage();
        } else {
            $errorMessage = '系统错误，请稍后重试';
        }
        
        return json([
            'code' => 500,
            'message' => $errorMessage,
            'data' => null
        ], 500);
    }
}
