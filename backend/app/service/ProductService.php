<?php

namespace app\service;

use app\model\Product;
use app\model\Category;
use think\exception\ValidateException;

/**
 * 商品服务类
 * 处理商品相关业务逻辑
 * 验证需求: 3.1, 3.4
 */
class ProductService
{
    /**
     * 创建商品
     * 验证需求: 3.1
     * 
     * @param array $data 商品数据
     * @return Product
     * @throws ValidateException
     */
    public function createProduct(array $data)
    {
        // 验证价格必须大于0
        if (!isset($data['price']) || $data['price'] <= 0) {
            throw new ValidateException('价格必须大于0');
        }
        
        // 验证库存不能为负数
        if (isset($data['stock']) && $data['stock'] < 0) {
            throw new ValidateException('库存不能为负数');
        }
        
        // 验证分类存在
        if (!isset($data['category_id'])) {
            throw new ValidateException('分类ID不能为空');
        }
        
        $category = Category::find($data['category_id']);
        if (!$category) {
            throw new ValidateException('分类不存在');
        }
        
        // 设置默认值
        if (!isset($data['stock'])) {
            $data['stock'] = 0;
        }
        
        if (!isset($data['status'])) {
            $data['status'] = 'off_sale';
        }
        
        // 创建商品
        $product = Product::create($data);
        
        return $product;
    }
    
    /**
     * 更新商品
     * 验证需求: 3.4
     * 
     * @param int $id 商品ID
     * @param array $data 更新数据
     * @return Product
     * @throws \Exception
     */
    public function updateProduct(int $id, array $data)
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \Exception('商品不存在');
        }
        
        // 验证价格必须大于0
        if (isset($data['price']) && $data['price'] <= 0) {
            throw new ValidateException('价格必须大于0');
        }
        
        // 验证库存不能为负数
        if (isset($data['stock']) && $data['stock'] < 0) {
            throw new ValidateException('库存不能为负数');
        }
        
        // 验证分类存在
        if (isset($data['category_id'])) {
            $category = Category::find($data['category_id']);
            if (!$category) {
                throw new ValidateException('分类不存在');
            }
        }
        
        // 更新商品
        $product->save($data);
        
        return $product;
    }
    
    /**
     * 删除商品（软删除）
     * 验证需求: 3.5
     * 
     * @param int $id 商品ID
     * @return bool
     * @throws \Exception
     */
    public function deleteProduct(int $id)
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \Exception('商品不存在');
        }
        
        // 软删除
        return $product->delete();
    }
    
    /**
     * 更新商品状态（上架/下架）
     * 验证需求: 3.6, 3.7
     * 
     * @param int $id 商品ID
     * @param string $status 状态（on_sale:上架 off_sale:下架）
     * @return Product
     * @throws \Exception
     */
    public function updateStatus(int $id, string $status)
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \Exception('商品不存在');
        }
        
        if (!in_array($status, ['on_sale', 'off_sale'])) {
            throw new ValidateException('状态值无效');
        }
        
        $product->status = $status;
        $product->save();
        
        return $product;
    }
    
    /**
     * 获取商品列表（分页）
     * 验证需求: 3.8
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string|null $search 搜索关键词（商品名称）
     * @param int|null $categoryId 分类ID
     * @param bool $onSaleOnly 是否只获取上架商品
     * @return \think\Paginator
     */
    public function getProductList(
        int $page = 1,
        int $pageSize = 10,
        ?string $search = null,
        ?int $categoryId = null,
        bool $onSaleOnly = false
    ) {
        $query = Product::withoutGlobalScope();
        
        // 搜索功能：支持按商品名称进行模糊搜索
        if ($search) {
            $query->whereLike('name', '%' . $search . '%');
        }
        
        // 按分类筛选
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // 只获取上架商品
        if ($onSaleOnly) {
            $query->where('status', 'on_sale');
        }
        
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
    }
    
    /**
     * 根据ID获取商品
     * 
     * @param int $id 商品ID
     * @return Product|null
     */
    public function getProductById(int $id)
    {
        return Product::find($id);
    }
    
    /**
     * 减少商品库存
     * 
     * @param int $id 商品ID
     * @param int $quantity 数量
     * @return Product
     * @throws \Exception
     */
    public function decreaseStock(int $id, int $quantity)
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \Exception('商品不存在');
        }
        
        if ($product->stock < $quantity) {
            throw new \Exception('商品库存不足');
        }
        
        $product->stock -= $quantity;
        $product->save();
        
        return $product;
    }
    
    /**
     * 增加商品库存
     * 
     * @param int $id 商品ID
     * @param int $quantity 数量
     * @return Product
     * @throws \Exception
     */
    public function increaseStock(int $id, int $quantity)
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \Exception('商品不存在');
        }
        
        $product->stock += $quantity;
        $product->save();
        
        return $product;
    }
}
