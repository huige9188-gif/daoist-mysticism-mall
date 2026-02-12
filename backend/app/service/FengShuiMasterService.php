<?php

namespace app\service;

use app\model\FengShuiMaster;
use think\exception\ValidateException;

/**
 * 风水师服务类
 * 处理风水师相关业务逻辑
 * 验证需求: 8.1, 8.3
 */
class FengShuiMasterService
{
    /**
     * 创建风水师
     * 验证需求: 8.1
     * 
     * @param array $data 风水师数据
     * @return FengShuiMaster
     * @throws ValidateException
     */
    public function createFengShuiMaster(array $data)
    {
        // 验证姓名不能为空
        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new ValidateException('风水师姓名不能为空');
        }
        
        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }
        
        // 创建风水师
        $master = FengShuiMaster::create($data);
        
        return $master;
    }
    
    /**
     * 更新风水师
     * 验证需求: 8.3
     * 
     * @param int $id 风水师ID
     * @param array $data 更新数据
     * @return FengShuiMaster
     * @throws \Exception
     */
    public function updateFengShuiMaster(int $id, array $data)
    {
        $master = FengShuiMaster::find($id);
        if (!$master) {
            throw new \Exception('风水师不存在');
        }
        
        // 验证姓名不能为空
        if (isset($data['name']) && trim($data['name']) === '') {
            throw new ValidateException('风水师姓名不能为空');
        }
        
        // 更新风水师
        $master->save($data);
        
        return $master;
    }
    
    /**
     * 删除风水师（软删除）
     * 验证需求: 8.4
     * 
     * @param int $id 风水师ID
     * @return bool
     * @throws \Exception
     */
    public function deleteFengShuiMaster(int $id)
    {
        $master = FengShuiMaster::find($id);
        if (!$master) {
            throw new \Exception('风水师不存在');
        }
        
        // 软删除
        return $master->delete();
    }
    
    /**
     * 更新风水师状态（启用/禁用）
     * 验证需求: 8.5
     * 
     * @param int $id 风水师ID
     * @param int $status 状态（1:启用 0:禁用）
     * @return FengShuiMaster
     * @throws \Exception
     */
    public function updateStatus(int $id, int $status)
    {
        $master = FengShuiMaster::find($id);
        if (!$master) {
            throw new \Exception('风水师不存在');
        }
        
        if (!in_array($status, [0, 1])) {
            throw new ValidateException('状态值无效');
        }
        
        $master->status = $status;
        $master->save();
        
        return $master;
    }
    
    /**
     * 获取风水师列表（分页）
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param bool $enabledOnly 是否只获取启用的风水师
     * @return \think\Paginator
     */
    public function getFengShuiMasterList(
        int $page = 1,
        int $pageSize = 10,
        bool $enabledOnly = false
    ) {
        $query = FengShuiMaster::withoutGlobalScope();
        
        // 只获取启用的风水师
        if ($enabledOnly) {
            $query->where('status', 1);
        }
        
        return $query->order('created_at', 'desc')->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
    }
    
    /**
     * 根据ID获取风水师
     * 
     * @param int $id 风水师ID
     * @return FengShuiMaster|null
     */
    public function getFengShuiMasterById(int $id)
    {
        return FengShuiMaster::find($id);
    }
}
