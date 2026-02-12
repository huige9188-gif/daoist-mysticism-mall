<?php

namespace app\controller\api;

use app\BaseController;
use app\service\VideoService;
use think\Request;
use think\exception\ValidateException;

/**
 * 视频管理控制器
 * 
 * 验证需求: 6.1-6.6
 */
class Video extends BaseController
{
    protected $videoService;
    
    public function __construct()
    {
        $this->videoService = new VideoService();
    }
    
    /**
     * 获取视频列表（支持分页）
     * GET /api/videos
     * 
     * 验证需求: 6.6
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        $enabledOnly = $request->get('enabled_only', false);
        
        $videos = $this->videoService->getVideoList(
            $page,
            $pageSize,
            $enabledOnly
        );
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $videos
        ]);
    }
    
    /**
     * 创建视频
     * POST /api/videos
     * 
     * 验证需求: 6.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            $video = $this->videoService->createVideo($data);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $video
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    /**
     * 更新视频
     * PUT /api/videos/:id
     * 
     * 验证需求: 6.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->put();
            
            $video = $this->videoService->updateVideo($id, $data);
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $video
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
    
    /**
     * 删除视频（软删除）
     * DELETE /api/videos/:id
     * 
     * 验证需求: 6.5
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $this->videoService->deleteVideo($id);
            
            return json([
                'code' => 200,
                'message' => '删除成功',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
    
    /**
     * 更新视频状态（启用/禁用）
     * PATCH /api/videos/:id/status
     * 
     * 验证需求: 6.6
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $status = $request->patch('status');
            
            if ($status === null) {
                return json([
                    'code' => 400,
                    'message' => '状态参数不能为空',
                    'data' => null
                ], 400);
            }
            
            $video = $this->videoService->updateStatus($id, $status);
            
            return json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => $video
            ]);
        } catch (ValidateException $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
}
