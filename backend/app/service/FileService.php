<?php

namespace app\service;

use think\exception\ValidateException;
use think\facade\Filesystem;

/**
 * 文件服务类
 * 处理文件上传和管理
 * 验证需求: 15.1, 15.2, 15.3, 15.5, 15.6
 */
class FileService
{
    /**
     * 允许的图片文件类型
     */
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif'];
    
    /**
     * 最大文件大小（字节）5MB
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    
    /**
     * 上传图片文件
     * 验证需求: 15.1, 15.2, 15.3, 15.5, 15.6
     * 
     * @param \think\file\UploadedFile $file 上传的文件对象
     * @return array 返回包含url和path的数组
     * @throws ValidateException
     */
    public function uploadImage($file)
    {
        // 验证文件类型 (需求 15.1)
        $extension = strtolower($file->extension());
        if (!in_array($extension, self::ALLOWED_IMAGE_TYPES)) {
            throw new ValidateException('不支持的文件类型，仅支持jpg、jpeg、png、gif格式');
        }
        
        // 验证文件大小 (需求 15.2)
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new ValidateException('文件大小超过限制，最大支持5MB');
        }
        
        // 生成唯一文件名 (需求 15.5)
        $filename = $this->generateUniqueFilename($extension);
        
        // 按日期组织目录 (需求 15.6)
        $directory = 'uploads/' . date('Y/m/d');
        
        // 保存文件
        $path = $directory . '/' . $filename;
        $file->move(public_path() . '/' . $directory, $filename);
        
        // 返回访问URL和路径 (需求 15.3)
        return [
            'url' => request()->domain() . '/' . $path,
            'path' => $path
        ];
    }
    
    /**
     * 生成唯一文件名
     * 使用时间戳、微秒和随机字符串确保唯一性
     * 验证需求: 15.5
     * 
     * @param string $extension 文件扩展名
     * @return string 唯一文件名
     */
    private function generateUniqueFilename(string $extension): string
    {
        // 使用时间戳 + 微秒 + 随机字符串生成唯一文件名
        $timestamp = time();
        $microtime = microtime(true);
        $random = bin2hex(random_bytes(8));
        
        return sprintf(
            '%s_%s_%s.%s',
            $timestamp,
            str_replace('.', '', $microtime),
            $random,
            $extension
        );
    }
}
