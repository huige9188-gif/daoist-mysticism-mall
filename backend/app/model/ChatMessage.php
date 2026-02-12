<?php

namespace app\model;

use think\Model;

/**
 * 聊天消息模型
 */
class ChatMessage extends Model
{
    // 设置表名
    protected $name = 'chat_messages';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'session_id' => 'integer',
        'sender_id' => 'integer',
        'created_at' => 'datetime',
    ];
    
    /**
     * 关联会话
     */
    public function session()
    {
        return $this->belongsTo('ChatSession', 'session_id', 'id');
    }
    
    /**
     * 关联发送者
     */
    public function sender()
    {
        return $this->belongsTo('User', 'sender_id', 'id');
    }
}
