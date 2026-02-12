<?php

namespace app\controller\api;

use app\BaseController;
use app\service\CategoryService;
use think\Request;
use think\exception\ValidateException;

/**
 * 商品分类管理控制器
 * 
 * 验证需求: 4.1, 4.3, 4.4, 4.6
 */
class Category extends BaseController
{
    protected $categoryService;
    
    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }
    
    /**
     * 获取分类列表
     * GET /api/categories
     * 
     * 验证需求: 4.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $activeOnly = $request->get('active_only', false);
        
        $categories = $this->categoryService->getCategoryList($activeOnly);
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $categories
        ]);
    }
    
    /**
     * 创建分类
     * POST /api/categories
     * 
     * 验证需求: 4.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            $category = $this->categoryService->createCategory($data);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $category
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
     * 更新分类
     * PUT /api/categories/:id
     * 
     * 验证需求: 4.3
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->put();
            
            $category = $this->categoryService->updateCategory($id, $data);
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $category
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
     * 删除分类
     * DELETE /api/categories/:id
     * 
     * 验证需求: 4.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $this->categoryService->deleteCategory($id);
            
            return json([
                'code' => 200,
                'message' => '删除成功',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
    
    /**
     * 更新分类状态
     * PATCH /api/categories/:id/status
     * 
     * 验证需求: 4.6
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
            
            $category = $this->categoryService->updateStatus($id, $status);
            
            return json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => $category
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
