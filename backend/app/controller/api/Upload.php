<?php

namespace app\controller\api;

use app\BaseController;
use app\service\FileService;
use think\Request;
use think\exception\ValidateException;

/**
 * 文件上传控制器
 * 
 * 验证需求: 15.1, 15.2, 15.3, 15.4
 */
class Upload extends BaseController
{
    protected $fileService;
    
    public function __construct()
    {
        $this->fileService = new FileService();
    }
    
    /**
     * 上传图片文件
     * POST /api/upload/image
     * 
     * 验证需求: 15.1, 15.2, 15.3, 15.4
     * 
     * 接收图片文件上传，验证文件类型和大小，保存文件并返回访问URL
     * 
     * 请求参数:
     * - file: 上传的图片文件（multipart/form-data）
     * 
     * 响应数据:
     * - url: 文件访问URL
     * - path: 文件相对路径
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function image(Request $request)
    {
        try {
            // 获取上传的文件
            $file = $request->file('file');
            
            // 验证文件是否存在
            if (!$file) {
                return json([
                    'code' => 400,
                    'message' => '请选择要上传的文件',
                    'data' => null
                ], 400);
            }
            
            // 调用文件服务上传图片
            $result = $this->fileService->uploadImage($file);
            
            return json([
                'code' => 200,
                'message' => '上传成功',
                'data' => $result
            ]);
            
        } catch (ValidateException $e) {
            // 验证失败（文件类型或大小不符合要求）
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
            
        } catch (\Exception $e) {
            // 其他错误
            return json([
                'code' => 500,
                'message' => '上传失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
