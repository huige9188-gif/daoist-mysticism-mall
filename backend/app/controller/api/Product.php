<?php

namespace app\controller\api;

use app\BaseController;
use app\service\ProductService;
use think\Request;
use think\exception\ValidateException;

/**
 * 商品管理控制器
 * 
 * 验证需求: 3.1, 3.4, 3.5, 3.6, 3.7, 3.8
 */
class Product extends BaseController
{
    protected $productService;
    
    public function __construct()
    {
        $this->productService = new ProductService();
    }
    
    /**
     * 获取商品列表（支持搜索和分页）
     * GET /api/products
     * 
     * 验证需求: 3.8
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        $search = $request->get('search', null);
        $categoryId = $request->get('category_id', null);
        $onSaleOnly = $request->get('on_sale_only', false);
        
        $products = $this->productService->getProductList(
            $page,
            $pageSize,
            $search,
            $categoryId,
            $onSaleOnly
        );
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $products
        ]);
    }
    
    /**
     * 创建商品
     * POST /api/products
     * 
     * 验证需求: 3.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            $product = $this->productService->createProduct($data);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $product
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
     * 更新商品
     * PUT /api/products/:id
     * 
     * 验证需求: 3.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->put();
            
            $product = $this->productService->updateProduct($id, $data);
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $product
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
     * 删除商品（软删除）
     * DELETE /api/products/:id
     * 
     * 验证需求: 3.5
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $this->productService->deleteProduct($id);
            
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
     * 更新商品状态（上架/下架）
     * PATCH /api/products/:id/status
     * 
     * 验证需求: 3.6, 3.7
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
            
            $product = $this->productService->updateStatus($id, $status);
            
            return json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => $product
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
