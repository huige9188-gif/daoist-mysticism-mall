<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * CORS跨域中间件
 */
class CorsMiddleware
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
        // 处理预检请求
        if ($request->method(true) === 'OPTIONS') {
            return $this->setCorsHeaders(response());
        }

        $response = $next($request);

        return $this->setCorsHeaders($response);
    }

    /**
     * 设置CORS响应头
     *
     * @param Response $response
     * @return Response
     */
    private function setCorsHeaders(Response $response): Response
    {
        $response->header([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ]);

        return $response;
    }
}
