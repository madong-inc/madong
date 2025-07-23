<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: https://madong.tech
 */

namespace app\common\scopes\query\user;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DataAuthScope implements Scope
{

    /**
     * 数据权限【1=>默认所有，2=>自定义数据权限，3=>本部门数据权限，4=>本部门及以下数据权限,5=>本人数据权限】
     *
     * @var array
     */
    private array $scope = [];

    /**
     * 权限列表
     *
     * @var array|int[]
     */
    private array $data = [];

    public function apply(Builder $builder, Model $model)
    {
        $adminInfo = getCurrentUser(true);
        if (!empty($adminInfo)) {
            $this->scope = $adminInfo['data_scope'] ?? [1];
            $this->data  = $adminInfo['data_auth'] ?? [0];
            //顶级管理员跳过处理
            if ((int)$adminInfo['is_super'] === 1) {
                $this->scope=[1];
            }
            //非全部权限的时候做处理
            if (!in_array(1, $this->scope)) {
                $this->dataAuthority($builder, $model);
            }
        }
    }

    /**
     * 构造权限
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     */
    private function dataAuthority(Builder $builder, Model $model)
    {
        $isDirectDept = $model->isFillable('dept_id');
        if ($isDirectDept) {
            if (in_array(5, $this->scope)) {
                $uid = getCurrentUser();
                $builder->whereIn('dept_id', $this->data)->orWhere('created_by', $uid);
            } else {
                $builder->whereIn('dept_id', $this->data);
            }
        }
    }
}
