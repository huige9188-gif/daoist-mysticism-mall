<?php

namespace app\service;

use app\model\Article;
use think\exception\ValidateException;

/**
 * 文章服务类
 * 处理文章相关业务逻辑
 * 验证需求: 7.1, 7.4
 */
class ArticleService
{
    /**
     * 创建文章
     * 验证需求: 7.1
     * 
     * @param array $data 文章数据
     * @return Article
     * @throws ValidateException
     */
    public function createArticle(array $data)
    {
        // 验证标题不能为空
        if (!isset($data['title']) || trim($data['title']) === '') {
            throw new ValidateException('文章标题不能为空');
        }
        
        // 验证内容不能为空
        if (!isset($data['content']) || trim($data['content']) === '') {
            throw new ValidateException('文章内容不能为空');
        }
        
        // 设置默认状态为草稿
        if (!isset($data['status'])) {
            $data['status'] = Article::STATUS_DRAFT;
        }
        
        // 创建文章
        $article = Article::create($data);
        
        return $article;
    }
    
    /**
     * 更新文章
     * 验证需求: 7.4
     * 
     * @param int $id 文章ID
     * @param array $data 更新数据
     * @return Article
     * @throws \Exception
     */
    public function updateArticle(int $id, array $data)
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \Exception('文章不存在');
        }
        
        // 验证标题不能为空
        if (isset($data['title']) && trim($data['title']) === '') {
            throw new ValidateException('文章标题不能为空');
        }
        
        // 验证内容不能为空
        if (isset($data['content']) && trim($data['content']) === '') {
            throw new ValidateException('文章内容不能为空');
        }
        
        // 更新文章
        $article->save($data);
        
        return $article;
    }
    
    /**
     * 删除文章（软删除）
     * 验证需求: 7.5
     * 
     * @param int $id 文章ID
     * @return bool
     * @throws \Exception
     */
    public function deleteArticle(int $id)
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \Exception('文章不存在');
        }
        
        // 软删除
        return $article->delete();
    }
    
    /**
     * 发布文章
     * 验证需求: 7.6
     * 
     * @param int $id 文章ID
     * @return Article
     * @throws \Exception
     */
    public function publishArticle(int $id)
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \Exception('文章不存在');
        }
        
        $article->status = Article::STATUS_PUBLISHED;
        $article->save();
        
        return $article;
    }
    
    /**
     * 撤回文章（设置为草稿）
     * 验证需求: 7.7
     * 
     * @param int $id 文章ID
     * @return Article
     * @throws \Exception
     */
    public function unpublishArticle(int $id)
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \Exception('文章不存在');
        }
        
        $article->status = Article::STATUS_DRAFT;
        $article->save();
        
        return $article;
    }
    
    /**
     * 更新文章状态
     * 验证需求: 7.6, 7.7
     * 
     * @param int $id 文章ID
     * @param string $status 状态（draft或published）
     * @return Article
     * @throws \Exception
     */
    public function updateStatus(int $id, string $status)
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \Exception('文章不存在');
        }
        
        if (!in_array($status, [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])) {
            throw new ValidateException('状态值无效');
        }
        
        $article->status = $status;
        $article->save();
        
        return $article;
    }
    
    /**
     * 获取文章列表（分页）
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param bool $publishedOnly 是否只获取已发布的文章
     * @return \think\Paginator
     */
    public function getArticleList(
        int $page = 1,
        int $pageSize = 10,
        bool $publishedOnly = false
    ) {
        $query = Article::withoutGlobalScope();
        
        // 只获取已发布的文章
        if ($publishedOnly) {
            $query->where('status', Article::STATUS_PUBLISHED);
        }
        
        return $query->order('created_at', 'desc')->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
    }
    
    /**
     * 根据ID获取文章
     * 
     * @param int $id 文章ID
     * @return Article|null
     */
    public function getArticleById(int $id)
    {
        return Article::find($id);
    }
}
