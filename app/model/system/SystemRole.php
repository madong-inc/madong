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

namespace app\model\system;

use madong\basic\BaseTpORMModel;

/**
 * 角色模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemRole extends BaseTpORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_role';

    /**
     * Id搜索
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 角色名称-搜索器
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

    /**
     * 通过中间表获取菜单
     */
    public function menus(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(SystemMenu::class, SystemRoleMenu::class, 'menu_id', 'role_id');
    }

    /**
     * 通过中间表获取部门
     */
    public function depts(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(SystemDept::class, SystemRoleDept::class, 'dept_id', 'role_id');
    }

}
