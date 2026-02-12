<?php

namespace app\controller\api;

use app\BaseController;
use app\service\FengShuiMasterService;
use think\Request;
use think\exception\ValidateException;

/**
 * 风水师管理控制器
 * 
 * 验证需求: 8.1-8.5
 */
class FengShuiMaster extends BaseController
{
    protected $fengShuiMasterService;
    
    public function __construct()
    {
        $this->fengShuiMasterService = new FengShuiMasterService();
    }
    
    /**
     * 获取风水师列表（支持分页）
     * GET /api/feng-shui-masters
     * 
     * 验证需求: 8.5
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        $enabledOnly = $request->get('enabled_only', false);
        
        $masters = $this->fengShuiMasterService->getFengShuiMasterList(
            $page,
            $pageSize,
            $enabledOnly
        );
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $masters
        ]);
    }
    
    /**
     * 创建风水师
     * POST /api/feng-shui-masters
     * 
     * 验证需求: 8.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            $master = $this->fengShuiMasterService->createFengShuiMaster($data);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $master
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
     * 更新风水师
     * PUT /api/feng-shui-masters/:id
     * 
     * 验证需求: 8.3
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->put();
            
            $master = $this->fengShuiMasterService->updateFengShuiMaster($id, $data);
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $master
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
     * 删除风水师（软删除）
     * DELETE /api/feng-shui-masters/:id
     * 
     * 验证需求: 8.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $this->fengShuiMasterService->deleteFengShuiMaster($id);
            
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
     * 更新风水师状态（启用/禁用）
     * PATCH /api/feng-shui-masters/:id/status
     * 
     * 验证需求: 8.5
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
            
            $master = $this->fengShuiMasterService->updateStatus($id, $status);
            
            return json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => $master
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
