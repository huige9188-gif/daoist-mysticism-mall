<?php

namespace app\controller\api;

use app\BaseController;
use app\service\StatisticsService;
use think\Request;

/**
 * 仪表盘控制器
 * 
 * 验证需求: 1.1, 1.2, 1.3
 */
class Dashboard extends BaseController
{
    protected $statisticsService;
    
    public function __construct()
    {
        $this->statisticsService = new StatisticsService();
    }
    
    /**
     * 获取仪表盘统计数据
     * GET /api/dashboard/stats
     * 
     * 验证需求: 1.1, 1.2, 1.3
     * 
     * 返回系统关键数据统计，包括：
     * - 总订单数、总销售额、总用户数、总商品数
     * - 各订单状态的数量统计
     * - 最近10条订单记录
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function stats(Request $request)
    {
        $data = $this->statisticsService->getDashboardData();
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data
        ]);
    }
}
