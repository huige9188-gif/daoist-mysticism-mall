<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * JSON响应格式化中间件
 * 
 * 验证需求: 13.2, 13.3, 13.6, 13.7
 */
class JsonResponseMiddleware
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 确保响应是JSON格式
        if (!$response->getHeader('Content-Type')) {
            $response->contentType('application/json');
        }

        // 如果响应数据不是JSON格式，尝试转换
        $content = $response->getContent();
        
        // 检查是否已经是JSON格式
        if (!$this->isJson($content)) {
            // 如果不是JSON，尝试将其包装为统一格式
            $data = $content;
            
            // 如果是字符串，包装为统一响应格式
            if (is_string($content) && !empty($content)) {
                $response->data([
                    'code' => 200,
                    'message' => 'success',
                    'data' => $content
                ]);
            }
        }

        return $response;
    }

    /**
     * 检查字符串是否为有效的JSON
     *
     * @param string $string
     * @return bool
     */
    private function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
