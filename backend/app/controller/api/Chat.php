<?php

namespace app\controller\api;

use app\BaseController;
use app\service\ChatService;
use think\Request;
use think\exception\ValidateException;

/**
 * 客服管理控制器
 * 
 * 验证需求: 10.1, 10.4, 10.5, 10.6
 */
class Chat extends BaseController
{
    protected $chatService;
    
    public function __construct()
    {
        $this->chatService = new ChatService();
    }
    
    /**
     * 获取会话列表
     * GET /api/chat/sessions
     * 
     * 验证需求: 10.4
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function sessions(Request $request)
    {
        try {
            // 获取查询参数
            $status = $request->get('status', 'active');
            
            // 根据状态获取会话列表
            if ($status === 'active') {
                $sessions = $this->chatService->getActiveSessions();
            } else {
                // 可以扩展支持其他状态
                $sessions = $this->chatService->getActiveSessions();
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $sessions->toArray()
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    /**
     * 获取会话聊天记录
     * GET /api/chat/sessions/:id/messages
     * 
     * 验证需求: 10.5
     * 
     * @param Request $request
     * @param int $id 会话ID
     * @return \think\Response
     */
    public function messages(Request $request, $id)
    {
        try {
            $messages = $this->chatService->getSessionMessages($id);
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $messages->toArray()
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
     * 创建会话
     * POST /api/chat/sessions
     * 
     * 验证需求: 10.1
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function createSession(Request $request)
    {
        try {
            // 获取当前用户ID（从认证中间件注入）
            $userId = $request->user['id'] ?? null;
            
            if (!$userId) {
                return json([
                    'code' => 401,
                    'message' => '未授权访问',
                    'data' => null
                ], 401);
            }
            
            $session = $this->chatService->createSession($userId);
            
            return json([
                'code' => 200,
                'message' => '会话创建成功',
                'data' => $session
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
     * 结束会话
     * POST /api/chat/sessions/:id/close
     * 
     * 验证需求: 10.6
     * 
     * @param Request $request
     * @param int $id 会话ID
     * @return \think\Response
     */
    public function closeSession(Request $request, $id)
    {
        try {
            $session = $this->chatService->closeSession($id);
            
            return json([
                'code' => 200,
                'message' => '会话已结束',
                'data' => $session
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
}
