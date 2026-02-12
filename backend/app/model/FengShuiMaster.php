<?php

namespace app\model;

use think\Model;

/**
 * 风水师模型
 */
class FengShuiMaster extends Model
{
    // 设置表名
    protected $name = 'feng_shui_masters';
    
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
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * 检查风水师是否启用
     * @return bool
     */
    public function isEnabled()
    {
        return $this->status === 1;
    }
}
