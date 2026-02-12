<?php

namespace app\common;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Exception;

/**
 * JWT工具类
 * 负责JWT令牌的生成和验证
 */
class Jwt
{
    /**
     * 生成JWT令牌
     *
     * @param array $payload 载荷数据（用户信息）
     * @return string JWT令牌
     */
    public static function generateToken(array $payload): string
    {
        $secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
        $expire = getenv('JWT_EXPIRE') ?: 7200;
        
        // 添加标准声明
        $payload['iat'] = time(); // 签发时间
        $payload['exp'] = time() + $expire; // 过期时间
        
        return FirebaseJWT::encode($payload, $secret, 'HS256');
    }
    
    /**
     * 验证JWT令牌
     *
     * @param string $token JWT令牌
     * @return object|null 解码后的载荷数据，验证失败返回null
     */
    public static function verifyToken(string $token): ?object
    {
        try {
            $secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
            $decoded = FirebaseJWT::decode($token, new Key($secret, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            // 令牌无效或过期
            return null;
        }
    }
    
    /**
     * 从令牌中提取用户信息
     *
     * @param string $token JWT令牌
     * @return array|null 用户信息数组，验证失败返回null
     */
    public static function getUserFromToken(string $token): ?array
    {
        $decoded = self::verifyToken($token);
        
        if ($decoded === null) {
            return null;
        }
        
        // 将对象转换为数组
        return json_decode(json_encode($decoded), true);
    }
}
