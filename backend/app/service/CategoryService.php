<?php

namespace app\service;

use app\model\Category;
use think\exception\ValidateException;

/**
 * 商品分类服务类
 * 处理商品分类相关业务逻辑
 * 验证需求: 4.1, 4.3
 */
class CategoryService
{
    /**
     * 创建分类
     * 验证需求: 4.1
     * 
     * @param array $data 分类数据
     * @return Category
     * @throws ValidateException
     */
    public function createCategory(array $data)
    {
        // 验证分类名称非空
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new ValidateException('分类名称不能为空');
        }
        
        // 设置默认值
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = 0;
        }
        
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }
        
        // 创建分类
        $category = Category::create($data);
        
        return $category;
    }
    
    /**
     * 更新分类
     * 验证需求: 4.3
     * 
     * @param int $id 分类ID
     * @param array $data 更新数据
     * @return Category
     * @throws \Exception
     */
    public function updateCategory(int $id, array $data)
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('分类不存在');
        }
        
        // 验证分类名称非空
        if (isset($data['name']) && (empty($data['name']) || trim($data['name']) === '')) {
            throw new ValidateException('分类名称不能为空');
        }
        
        // 更新分类
        $category->save($data);
        
        return $category;
    }
    
    /**
     * 删除分类
     * 验证需求: 4.4
     * 
     * @param int $id 分类ID
     * @return bool
     * @throws \Exception
     */
    public function deleteCategory(int $id)
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('分类不存在');
        }
        
        // 检查该分类下是否有商品（如果Product模型存在）
        if (class_exists('app\model\Product')) {
            $productCount = $category->products()->count();
            if ($productCount > 0) {
                throw new \Exception('该分类下有商品，无法删除');
            }
        }
        
        // 软删除
        return $category->delete();
    }
    
    /**
     * 获取分类列表（按排序值排序）
     * 验证需求: 4.1, 4.5
     * 
     * @param bool $activeOnly 是否只获取启用的分类
     * @return \think\Collection
     */
    public function getCategoryList(bool $activeOnly = false)
    {
        $query = Category::order('sort_order', 'asc');
        
        if ($activeOnly) {
            $query->where('status', 1);
        }
        
        return $query->select();
    }
    
    /**
     * 更新分类状态
     * 验证需求: 4.6
     * 
     * @param int $id 分类ID
     * @param int $status 状态（1:启用 0:禁用）
     * @return Category
     * @throws \Exception
     */
    public function updateStatus(int $id, int $status)
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('分类不存在');
        }
        
        if (!in_array($status, [0, 1])) {
            throw new ValidateException('状态值无效');
        }
        
        $category->status = $status;
        $category->save();
        
        return $category;
    }
    
    /**
     * 根据ID获取分类
     * 
     * @param int $id 分类ID
     * @return Category|null
     */
    public function getCategoryById(int $id)
    {
        return Category::find($id);
    }
    
    /**
     * 更新分类排序值
     * 验证需求: 4.5
     * 
     * @param int $id 分类ID
     * @param int $sortOrder 排序值
     * @return Category
     * @throws \Exception
     */
    public function updateSortOrder(int $id, int $sortOrder)
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('分类不存在');
        }
        
        $category->sort_order = $sortOrder;
        $category->save();
        
        return $category;
    }
}
