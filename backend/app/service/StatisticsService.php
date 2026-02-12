<?php

namespace app\service;

use app\model\Order;
use app\model\User;
use app\model\Product;

/**
 * 统计服务类
 * 提供数据统计功能
 * 验证需求: 1.1, 1.2, 1.3
 */
class StatisticsService
{
    /**
     * 获取仪表盘数据
     * 验证需求: 1.1, 1.2, 1.3
     * 
     * 返回系统关键数据统计，包括：
     * - 总订单数、总销售额、总用户数、总商品数
     * - 各订单状态的数量统计
     * - 最近10条订单记录
     * 
     * @return array
     */
    public function getDashboardData()
    {
        return [
            'totalOrders' => $this->getTotalOrders(),
            'totalSales' => $this->getTotalSales(),
            'totalUsers' => $this->getTotalUsers(),
            'totalProducts' => $this->getTotalProducts(),
            'orderStatusCounts' => $this->getOrderStatusCounts(),
            'recentOrders' => $this->getRecentOrders(),
        ];
    }
    
    /**
     * 获取总订单数
     * 验证需求: 1.1
     * 
     * @return int
     */
    public function getTotalOrders()
    {
        return Order::count();
    }
    
    /**
     * 获取总销售额
     * 验证需求: 1.1
     * 
     * 只统计已完成订单的销售额
     * 
     * @return float
     */
    public function getTotalSales()
    {
        return Order::where('status', Order::STATUS_COMPLETED)
            ->sum('total_amount');
    }
    
    /**
     * 获取总用户数
     * 验证需求: 1.1
     * 
     * @return int
     */
    public function getTotalUsers()
    {
        return User::count();
    }
    
    /**
     * 获取总商品数
     * 验证需求: 1.1
     * 
     * @return int
     */
    public function getTotalProducts()
    {
        return Product::count();
    }
    
    /**
     * 获取各订单状态的数量统计
     * 验证需求: 1.2
     * 
     * @return array
     */
    public function getOrderStatusCounts()
    {
        return [
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'paid' => Order::where('status', Order::STATUS_PAID)->count(),
            'shipped' => Order::where('status', Order::STATUS_SHIPPED)->count(),
            'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            'cancelled' => Order::where('status', Order::STATUS_CANCELLED)->count(),
        ];
    }
    
    /**
     * 获取最近10条订单记录
     * 验证需求: 1.3
     * 
     * 返回最新的10条订单，包含订单号、用户名、金额、状态和创建时间
     * 
     * @return array
     */
    public function getRecentOrders()
    {
        return Order::with(['user'])
            ->order('created_at', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
    }
}
