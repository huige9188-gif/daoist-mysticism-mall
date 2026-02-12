<?php

namespace app\service;

use app\model\Video;
use think\exception\ValidateException;

/**
 * 视频服务类
 * 处理视频相关业务逻辑
 * 验证需求: 6.1, 6.4
 */
class VideoService
{
    /**
     * 创建视频
     * 验证需求: 6.1
     * 
     * @param array $data 视频数据
     * @return Video
     * @throws ValidateException
     */
    public function createVideo(array $data)
    {
        // 验证标题不能为空
        if (!isset($data['title']) || trim($data['title']) === '') {
            throw new ValidateException('视频标题不能为空');
        }
        
        // 验证视频URL不能为空
        if (!isset($data['video_url']) || trim($data['video_url']) === '') {
            throw new ValidateException('视频URL不能为空');
        }
        
        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }
        
        // 创建视频
        $video = Video::create($data);
        
        return $video;
    }
    
    /**
     * 更新视频
     * 验证需求: 6.4
     * 
     * @param int $id 视频ID
     * @param array $data 更新数据
     * @return Video
     * @throws \Exception
     */
    public function updateVideo(int $id, array $data)
    {
        $video = Video::find($id);
        if (!$video) {
            throw new \Exception('视频不存在');
        }
        
        // 验证标题不能为空
        if (isset($data['title']) && trim($data['title']) === '') {
            throw new ValidateException('视频标题不能为空');
        }
        
        // 验证视频URL不能为空
        if (isset($data['video_url']) && trim($data['video_url']) === '') {
            throw new ValidateException('视频URL不能为空');
        }
        
        // 更新视频
        $video->save($data);
        
        return $video;
    }
    
    /**
     * 删除视频（软删除）
     * 验证需求: 6.5
     * 
     * @param int $id 视频ID
     * @return bool
     * @throws \Exception
     */
    public function deleteVideo(int $id)
    {
        $video = Video::find($id);
        if (!$video) {
            throw new \Exception('视频不存在');
        }
        
        // 软删除
        return $video->delete();
    }
    
    /**
     * 更新视频状态（启用/禁用）
     * 验证需求: 6.6
     * 
     * @param int $id 视频ID
     * @param int $status 状态（1:启用 0:禁用）
     * @return Video
     * @throws \Exception
     */
    public function updateStatus(int $id, int $status)
    {
        $video = Video::find($id);
        if (!$video) {
            throw new \Exception('视频不存在');
        }
        
        if (!in_array($status, [0, 1])) {
            throw new ValidateException('状态值无效');
        }
        
        $video->status = $status;
        $video->save();
        
        return $video;
    }
    
    /**
     * 获取视频列表（分页）
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param bool $enabledOnly 是否只获取启用的视频
     * @return \think\Paginator
     */
    public function getVideoList(
        int $page = 1,
        int $pageSize = 10,
        bool $enabledOnly = false
    ) {
        $query = Video::withoutGlobalScope();
        
        // 只获取启用的视频
        if ($enabledOnly) {
            $query->where('status', 1);
        }
        
        return $query->order('created_at', 'desc')->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
    }
    
    /**
     * 根据ID获取视频
     * 
     * @param int $id 视频ID
     * @return Video|null
     */
    public function getVideoById(int $id)
    {
        return Video::find($id);
    }
}
