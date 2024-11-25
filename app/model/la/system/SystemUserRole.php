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


use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 用户管理角色-中间模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemUserRole extends Pivot
{
    protected $table = 'system_user_role';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 用户Id搜索器
     *
     * @param $query
     * @param $value
     */
    public function scopeUserId($query, $value)
    {
        if (!empty($value)) {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (is_array($value)) {
                $query->whereIn('user_id', $value);
            } else {
                $query->where('user_id', $value);
            }
        }
    }

    /**
     * 角色Id搜索器
     *
     * @param $query
     * @param $value
     */
    public function scopeRoleId($query, $value)
    {
        if (!empty($value)) {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (is_array($value)) {
                $query->whereIn('role_id', $value);
            } else {
                $query->where('role_id', $value);
            }
        }
    }

    /**
     * 定义与 User 的反向关联
     *
     * @return \think\model\relation\BelongsTo
     */
    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(SystemUser::class, 'user_id', 'id');
    }
}
