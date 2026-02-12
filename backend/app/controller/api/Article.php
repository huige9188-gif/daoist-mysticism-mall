<?php

namespace app\controller\api;

use app\BaseController;
use app\service\ArticleService;
use think\Request;
use think\exception\ValidateException;

/**
 * 文章管理控制器
 * 
 * 验证需求: 7.1-7.7
 */
class Article extends BaseController
{
    protected $articleService;
    
    public function __construct()
    {
        $this->articleService = new ArticleService();
    }
    
    /**
     * 获取文章列表（支持分页）
     * GET /api/articles
     * 
     * 验证需求: 7.6, 7.7
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        $publishedOnly = $request->get('published_only', false);
        
        $articles = $this->articleService->getArticleList(
            $page,
            $pageSize,
            $publishedOnly
        );
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $articles
        ]);
    }
    
    /**
     * 创建文章
     * POST /api/articles
     * 
     * 验证需求: 7.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function create(Request $request)
    {
        try {
            $data = $request->post();
            
            $article = $this->articleService->createArticle($data);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $article
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
     * 更新文章
     * PUT /api/articles/:id
     * 
     * 验证需求: 7.4
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->put();
            
            $article = $this->articleService->updateArticle($id, $data);
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $article
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
     * 删除文章（软删除）
     * DELETE /api/articles/:id
     * 
     * 验证需求: 7.5
     * 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $this->articleService->deleteArticle($id);
            
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
     * 更新文章状态（发布/撤回）
     * PATCH /api/articles/:id/status
     * 
     * 验证需求: 7.6, 7.7
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
            
            $article = $this->articleService->updateStatus($id, $status);
            
            return json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => $article
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
