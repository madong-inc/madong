<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.tech
 */

namespace app\common\dao\system;

use app\common\model\system\SysAdmin;
use InvalidArgumentException;
use core\abstract\BaseDao;
use core\context\TenantContext;

class SysAdminDao extends BaseDao
{

    protected function setModel(): string
    {
        return SysAdmin::class;
    }

    /**
     * 用户详情
     *
     * @param            $id
     * @param array|null $field
     * @param array|null $with
     * @param string     $order
     * @param array|null $withoutScopes
     *
     * @return SysAdmin|null
     * @throws \Exception
     */
    public function get($id, ?array $field = null, ?array $with = [], string $order = '', ?array $withoutScopes = null): SysAdmin|null
    {
        $result = $this->getModel()
            ->where('id', $id)
            ->with(['depts', 'posts', 'casbin.roles', 'tenant', 'managedTenants'])
            ->first()
            ->makeHidden(['password']);

        if ($result) {
            $result->role_id_list = [];
            $result->post_id_list = [];
            $result->dept_id      = '';

            // 处理 casbin 关系
            if ($result->casbin) {
                $roleIds = $result->casbin->flatMap(function ($item) {
                    return $item->roles->pluck('id');
                })->unique()->values()->all();

                $result->role_id_list = $roleIds;
            }

            // 处理 posts 关系
            if ($result->posts) {
                $result->post_id_list = $result->posts->pluck('id')->all();
            }

            // 处理 depts 关系
            if ($result->depts && $result->depts->isNotEmpty()) {
                $result->dept_id = $result->depts->first()->id;
            }
        }

        return $result;
    }

    /**
     * 获取用户列表
     *
     * @param array      $where
     * @param string     $field
     * @param int        $page
     * @param int        $limit
     * @param string     $order
     * @param array      $with
     * @param bool       $search
     * @param array|null $withoutScopes
     *
     * @return array
     * @throws \Exception
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false, ?array $withoutScopes = null): array
    {
        $where['enabled'] = 1;
        if (empty($with)) {
            $with = ['tenant', 'depts', 'posts', 'casbin.roles'];
        }
        $query = parent::selectModel($where, $field, $page, $limit, $order, $with, $search, $withoutScopes);
        $query->whereHas('tenant');
        $total = $query->count();
        $items = $query->get()->makeHidden(['password', 'backend_setting']);
        return [$total, $items];
    }

    /**
     * 平台管理-获取租户成员
     *
     * @param array      $where
     * @param string     $field
     * @param int        $page
     * @param int        $limit
     * @param string     $order
     * @param array      $with
     * @param bool       $search
     * @param array|null $withoutScopes
     *
     * @return array
     * @throws \Exception
     */
    public function getTenantMemberList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false, ?array $withoutScopes = null): array
    {
//        $where['enabled'] = 1;//显示禁用用户
        $where['is_tenant_admin'] = 1;
        if (empty($with)) {
            $with = ['managedTenants'];
        }
        $query = parent::selectModel($where, $field, $page, $limit, $order, $with, $search, $withoutScopes);
        $total = $query->count();
        $items = $query->get()->makeHidden(['password', 'backend_setting']);
        return [$total, $items];
    }

    /**
     * 获取用户列表-角色id
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     *
     * @return array
     * @throws \Exception
     */
    public function getUsersListByRoleId(array $where, string $field, int $page, int $limit): array
    {
        $roleId = $where['role_id'] ?? null;
        if (!$roleId) {
            throw new InvalidArgumentException("Role ID is required.");
        }
        $where['enabled']  = 1;//有效用户
        $where['is_super'] = 0;//非顶级管理员
        $tenantId          = TenantContext::getTenantId();
        if (!$tenantId) {
            throw new InvalidArgumentException("Tenant ID is required.");
        }

        $query = $this->getModel()->with(['tenant', 'roles'])
            ->whereHas('roles', function ($query) use ($roleId) {
                $query->where('id', $roleId);
            })->has('tenant');

        if (!empty($where)) {
            unset($where['role_id'], $where['tenant_id']);
            $query->where($where);
        }

        $total = $query->count();

        $items = $query->when($page > 0 && $limit > 0, function ($query) use ($page, $limit) {
            return $query->skip(($page - 1) * $limit)->take($limit);
        })
            ->select($field)
            ->get()
            ->toArray();

        return compact('total', 'items');
    }

    /**
     * 排除角色ID-用户列表
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     *
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getUsersExcludingRole(array $where, string $field, int $page, int $limit): array
    {
        $roleId = $where['role_id'] ?? null;
        if (!$roleId) {
            throw new InvalidArgumentException("Role ID is required.");
        }

        $where['enabled']  = 1;//有效用户
        $where['is_super'] = 0;//非顶级管理员

        // 获取排除的用户ID列表
        $sysAdminRoleDao  = new SysAdminRoleDao();
        $excludedAdminIds = $sysAdminRoleDao->getColumn(['role_id' => $roleId], 'admin_id');

        // 查询构建器
        $query = $this->getModel()->whereNotIn('id', $excludedAdminIds)
            ->with(['roles', 'tenant'])->has('tenant');

        // 如果有额外的条件，则添加到查询中
        if (!empty($where)) {
            unset($where['role_id']);
            $query->where($where);
        }

        $total = $query->count();

        // 如果没有用户，则返回空结果
        if ($total === 0) {
            return ['total' => 0, 'items' => []];
        }

        $items = $query->when($page > 0 && $limit > 0, function ($query) use ($page, $limit) {
            return $query->skip(($page - 1) * $limit)->take($limit);
        })->select($field)->get()->toArray();

        return compact('total', 'items');
    }

    /**
     * @param string|int $id
     *
     * @return \app\common\model\system\SysAdmin|null
     * @throws \Exception
     */
    public function getAdminById(string|int $id): ?SysAdmin
    {
        $result = $this->getModel()
            ->where('id', $id)
            ->with(['depts', 'posts', 'casbin.roles', 'tenant', 'managedTenants'])
            ->first()
            ->makeHidden(['password', 'backend_setting']);

        if ($result) {
            $result->role_id_list = [];
            $result->post_id_list = [];
            $result->dept_id      = '';

            // 处理 casbin 关系
            if ($result->casbin) {
                $roleIds = $result->casbin->flatMap(function ($item) {
                    return $item->roles->pluck('id');
                })->unique()->values()->all();

                $result->role_id_list = $roleIds;
            }

            // 处理 posts 关系
            if ($result->posts) {
                $result->post_id_list = $result->posts->pluck('id')->all();
            }

            // 处理 depts 关系
            if ($result->depts && $result->depts->isNotEmpty()) {
                $result->dept_id = $result->depts->first()->id;
            }
        }

        return $result;
    }

    /**
     * 获取用户详情
     *
     * @param string $name
     *
     * @return \app\common\model\system\SysAdmin|null
     * @throws \Exception
     */
    public function getAdminByName(string $name): ?SysAdmin
    {
        return $this->getModel()
            ->withoutGlobalScope('TenantScope')
            ->where('user_name', $name)
            ->with(['depts', 'posts', 'tenants', 'casbin.roles'])
            ->first();
    }

}
