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
 * 用户信息模型
 */
class SystemUser extends BaseTpORMModel
{



    // 完整数据库表名称
    protected $name = 'system_user';
    // 主键
    protected $pk = 'id';

    /**
     * 账号-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchUserNameAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('user_name', 'like', $value . '%');
        }
    }

    /**
     * 用户昵称-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchRealNameAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('real_name', 'like', $value . '%');
        }
    }

    /**
     * 用户参数-解析
     *
     * @param $value
     *
     * @return mixed
     */
    public function getBackendSettingAttr($value): mixed
    {
        return json_decode($value ?? '', true);
    }

    /**
     * 用户参数-转换
     *
     * @param $value
     *
     * @return bool|string
     */
    public function setBackendSettingAttr($value): bool|string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }


    /**
     * 定义与 UserRole 的关系
     *
     * @return \think\model\relation\HasMany
     */
    public function userRoles(): \think\model\relation\HasMany
    {
        return $this->hasMany(SystemUserRole::class, 'user_id', 'id');
    }


    /**
     * 通过中间表-关联角色
     */
    public function roles(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(SystemRole::class, SystemUserRole::class, 'role_id', 'user_id');
    }

    /**
     * 通过中间表-关联岗位
     */
    public function posts(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(SystemPost::class, SystemUserPost::class, 'post_id', 'user_id');
    }

    /**
     * 反向关联部门
     */
    public function depts(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(SystemDept::class, 'dept_id', 'id');
    }
}
