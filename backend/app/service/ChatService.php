<?php

namespace app\service;

use app\model\ChatSession;
use app\model\ChatMessage;
use think\exception\ValidateException;

/**
 * 客服服务类
 * 处理实时聊天业务逻辑
 * 验证需求: 10.1, 10.6, 10.7
 */
class ChatService
{
    /**
     * 创建聊天会话
     * 验证需求: 10.1
     * 
     * @param int $userId 用户ID
     * @return ChatSession
     * @throws \Exception
     */
    public function createSession(int $userId)
    {
        // 验证用户ID
        if ($userId <= 0) {
            throw new ValidateException('用户ID无效');
        }
        
        // 创建聊天会话
        $session = ChatSession::create([
            'user_id' => $userId,
            'status' => ChatSession::STATUS_ACTIVE,
            'started_at' => date('Y-m-d H:i:s'),
            'last_activity_at' => date('Y-m-d H:i:s'),
        ]);
        
        // TODO: 通知在线客服
        // notifyAdmins($session);
        
        return $session;
    }
    
    /**
     * 发送消息
     * 验证需求: 10.2, 10.3
     * 
     * @param int $sessionId 会话ID
     * @param int $senderId 发送者ID
     * @param string $content 消息内容
     * @return ChatMessage
     * @throws \Exception
     */
    public function sendMessage(int $sessionId, int $senderId, string $content)
    {
        // 查询会话
        $session = ChatSession::find($sessionId);
        if (!$session) {
            throw new \Exception('会话不存在');
        }
        
        // 验证消息内容不能为空
        if (empty(trim($content))) {
            throw new ValidateException('消息内容不能为空');
        }
        
        // 创建消息
        $message = ChatMessage::create([
            'session_id' => $sessionId,
            'sender_id' => $senderId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 更新会话最后活跃时间
        $session->last_activity_at = date('Y-m-d H:i:s');
        $session->save();
        
        return $message;
    }
    
    /**
     * 结束会话
     * 验证需求: 10.6
     * 
     * @param int $sessionId 会话ID
     * @return ChatSession
     * @throws \Exception
     */
    public function closeSession(int $sessionId)
    {
        // 查询会话
        $session = ChatSession::find($sessionId);
        if (!$session) {
            throw new \Exception('会话不存在');
        }
        
        // 更新会话状态
        $session->status = ChatSession::STATUS_CLOSED;
        $session->closed_at = date('Y-m-d H:i:s');
        $session->save();
        
        return $session;
    }
    
    /**
     * 检查不活跃会话（定时任务）
     * 验证需求: 10.7
     * 
     * 将超过30分钟无活动的活跃会话设置为不活跃状态
     * 
     * @return int 更新的会话数量
     */
    public function checkInactiveSessions()
    {
        // 计算30分钟前的时间
        $threshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        
        // 查找超过30分钟无活动的活跃会话
        $sessions = ChatSession::where('status', ChatSession::STATUS_ACTIVE)
            ->where('last_activity_at', '<', $threshold)
            ->select();
        
        $count = 0;
        
        // 更新会话状态为不活跃
        foreach ($sessions as $session) {
            $session->status = ChatSession::STATUS_INACTIVE;
            $session->save();
            $count++;
        }
        
        return $count;
    }
    
    /**
     * 获取活跃会话列表
     * 验证需求: 10.4
     * 
     * @return \think\Collection
     */
    public function getActiveSessions()
    {
        return ChatSession::with(['user'])
            ->where('status', ChatSession::STATUS_ACTIVE)
            ->order('last_activity_at', 'desc')
            ->select();
    }
    
    /**
     * 获取会话聊天记录
     * 验证需求: 10.5
     * 
     * @param int $sessionId 会话ID
     * @return \think\Collection
     * @throws \Exception
     */
    public function getSessionMessages(int $sessionId)
    {
        // 验证会话是否存在
        $session = ChatSession::find($sessionId);
        if (!$session) {
            throw new \Exception('会话不存在');
        }
        
        // 获取会话的所有消息，按时间顺序排列
        return ChatMessage::with(['sender'])
            ->where('session_id', $sessionId)
            ->order('created_at', 'asc')
            ->select();
    }
    
    /**
     * 根据ID获取会话
     * 
     * @param int $sessionId 会话ID
     * @return ChatSession|null
     */
    public function getSessionById(int $sessionId)
    {
        return ChatSession::with(['user', 'messages'])->find($sessionId);
    }
}
