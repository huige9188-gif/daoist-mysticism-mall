<?php

namespace app\model;

use think\Model;

/**
 * 聊天会话模型
 */
class ChatSession extends Model
{
    // 设置表名
    protected $name = 'chat_sessions';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'started_at';
    protected $updateTime = false;
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
    
    // 会话状态常量
    const STATUS_ACTIVE = 'active';      // 活跃
    const STATUS_INACTIVE = 'inactive';  // 不活跃
    const STATUS_CLOSED = 'closed';      // 已关闭
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }
    
    /**
     * 关联聊天消息
     */
    public function messages()
    {
        return $this->hasMany('ChatMessage', 'session_id', 'id');
    }
    
    /**
     * 检查会话是否活跃
     * @return bool
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * 检查会话是否不活跃
     * @return bool
     */
    public function isInactive()
    {
        return $this->status === self::STATUS_INACTIVE;
    }
    
    /**
     * 检查会话是否已关闭
     * @return bool
     */
    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }
    
    /**
     * 更新最后活跃时间
     */
    public function updateLastActivity()
    {
        $this->last_activity_at = date('Y-m-d H:i:s');
        $this->save();
    }
}
