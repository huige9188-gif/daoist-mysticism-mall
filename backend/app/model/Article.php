<?php

namespace app\model;

use think\Model;

/**
 * 文章模型
 */
class Article extends Model
{
    // 设置表名
    protected $name = 'articles';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = 'deleted_at';
    
    // 软删除
    use \think\model\concern\SoftDelete;
    
    // 隐藏字段
    protected $hidden = ['deleted_at'];
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // 状态常量
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    
    /**
     * 检查文章是否已发布
     * @return bool
     */
    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
    
    /**
     * 检查文章是否为草稿
     * @return bool
     */
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
