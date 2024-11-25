<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.cn
 */

namespace app\model\la\system;

use madong\basic\BaseLaORMModel;

/**
 * 部门模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemDept extends BaseLaORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $table = 'system_dept';


    /**
     * 部门名称-搜索器
     *
     * @param $query
     * @param $value
     */
    public function scopeName($query, $value)
    {
        if (!empty($value)) {
            $query->where('name', 'like', $value . '%');
        }
    }

    public function scopePid($query, $value)
    {
        if (!empty($value)) {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (is_array($value)) {
                $query->whereIn('pid', $value);
            } else {
                $query->where('pid', $value);
            }
        }
    }

    /**
     * 状态-搜索器
     *
     * @param $query
     * @param $value
     */
    public function scopeStatus($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    public function leader(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SystemUser::class, 'system_dept_leader', 'dept_id', 'user_id');
    }

}
