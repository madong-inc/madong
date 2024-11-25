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

namespace app\model\tp\system;

use madong\basic\BaseTpORMModel;

/**
 * 部门模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemDept extends BaseTpORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_dept';

//    protected $deleteTime = 'delete_time';
//    protected $defaultSoftDelete = null;

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;

    /**
     * 部门名称-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('name', 'like', $value . '%');
        }
    }

    public function searchPidAttr($query, $value)
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
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    public function leader(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(SystemUser::class, SystemDeptLeader::class, 'user_id', 'dept_id');
    }

}
