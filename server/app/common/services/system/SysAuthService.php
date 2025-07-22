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

namespace app\common\services\system;

use app\common\dao\system\SysAdminDao;
use app\common\dao\system\SysDeptDao;
use app\common\model\system\SysAdmin;
use app\common\model\system\SysRoleDept;
use app\common\services\platform\TenantPackageService;
use core\abstract\BaseService;
use core\casbin\Permission;
use core\context\TenantContext;
use core\enum\system\PolicyPrefix;
use madong\interface\IDict;
use support\Container;

class SysAuthService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysAdminDao::class);
    }

    /**
     * 数据权限【1=>默认所有，2=>自定义数据权限，3=>本部门数据权限，4=>本部门及以下数据权限,5=>本人数据权限】
     *
     * @param \app\common\model\system\SysAdmin $adminInfo
     */
    public function dataAuth(SysAdmin $adminInfo): void
    {
        $roles        = $adminInfo['roles'] ?? null;
        $depts        = $adminInfo['depts']['id'] ?? 0;
        $isSuperAdmin = (int)$adminInfo['is_super_admin'] ?? 0;
        $dataScope    = [];
        $dataAuth     = [];
        if (!empty($roles)) {
            $dataScope = array_unique(array_column($roles->toArray(), 'data_scope'));
        }
        if ($isSuperAdmin) {
            $dataScope = [1];
        }
        if (!in_array(1, $dataScope)) {
            if (in_array(2, $dataScope)) {
                $dataAuth = array_merge($dataAuth, (new SysRoleDept())->where('role_id', 2)->pluck('dept_id')->toArray());
            }
            if (in_array(3, $dataScope)) {
                $dataAuth = array_merge($dataAuth, [$depts]);
            }
            if (in_array(4, $dataScope)) {
                $dataAuth = array_merge($dataAuth, (new SysDeptDao())->getChildIdsIncludingSelf($depts));
            }
        }
        $adminInfo->set('data_auth', $dataAuth);
        $adminInfo->set('data_scope', $dataScope);
    }

    /**
     * 获取菜单
     *
     * @param \madong\interface\IDict $dict
     *
     * @return array
     */
    public function getMenusByUserRoles(IDict $dict, bool $includeButtons = false): array
    {
        $isSuperAdmin = boolval($dict->get('is_super', 0));
        $menuService  = new SysMenuService();
        $map1         = $includeButtons ? ['type' => [1, 2, 3, 4]] : ['type' => [1, 2]];
        if ($isSuperAdmin) {
            // 顶级管理员返回全部菜单
            return $menuService->getAllMenus($map1);
        }
        $userId             = $dict->get('id');
        $userName           = 'user:' . $userId;
        $domain             = 'domain:' . TenantContext::getTenantId();
        $adminTenant        = $dict->get('tenant', []);
        $isTenantSuperAdmin = boolval($adminTenant['is_super'] ?? 0);

        // 获取租户套餐权限id
        $resultValues = (new TenantPackageService())->getTenantPackagePermissions(TenantContext::getTenantId());

        if ($isTenantSuperAdmin) {
            // 租户管理员 - 返回套餐内所有权限
            return $this->getMenusByIds($menuService, $resultValues, $includeButtons);
        } else {
            // 普通成员 - 按角色分配权限
            $userPermissions = Permission::getImplicitPermissionsForUser($userName, $domain);
            // 过滤出包含在套餐权限内的权限
            $userFilteredPermissions = array_filter($userPermissions, function ($item) use ($resultValues, $includeButtons) {
                if (!$includeButtons) {
                    return isset($item[2]) && str_starts_with($item[2], PolicyPrefix::MENU->value) && in_array(str_replace(PolicyPrefix::MENU->value, '', $item[5]), $resultValues);
                }
                return true;
            });
            $userResultValues        = array_map(function ($item) {
                return str_replace(PolicyPrefix::MENU->value, '', $item);
            }, array_column($userFilteredPermissions, 5));

            return $this->getMenusByIds($menuService, array_unique($userResultValues), $includeButtons);
        }
    }

    /**
     * 根据IDS输出菜单
     *
     * @param \app\common\services\system\SysMenuService $menuService
     * @param array                                      $ids
     * @param bool                                       $includeButtons
     *
     * @return array
     */
    private function getMenusByIds(SysMenuService $menuService, array $ids, bool $includeButtons = false): array
    {
        $map1 = $includeButtons ? ['type' => [1, 2, 3, 4]] : ['type' => [1, 2]];
        if (empty($ids)) {
            return [];
        }
        $map1['id'] = $ids;
        return $menuService->getAllMenus($map1) ?? [];
    }

    /**
     * 获取用户角色-权限码
     *
     * @param \madong\interface\IDict $dict
     *
     * @return array
     */
    public function getCodesByUserRoles(IDict $dict): array
    {
        $isSuperAdmin = boolval($dict->get('is_super', 0));
        if ($isSuperAdmin) {
            // 顶级管理员
            return ['admin'];
        }

        $userName           = 'user:' . $dict->get('id');
        $domain             = 'domain:' . TenantContext::getTenantId();
        $adminTenant        = $dict->get('tenant', []);
        $isTenantSuperAdmin = boolval($adminTenant['is_super'] ?? 0);

        // 获取租户套餐权限id
        $resultValues = (new TenantPackageService())->getTenantPackagePermissionsCodes(TenantContext::getTenantId());

        if (!$isTenantSuperAdmin) {
            // 普通成员 - 按角色分配权限
            $userPermissions = Permission::getImplicitPermissionsForUser($userName, $domain);

            // 提取权限并仅保留带有 button 前缀的权限，去掉前缀
            $permissions = array_filter($userPermissions, function ($item) {
                return isset($item[2]) && str_starts_with($item[2], PolicyPrefix::BUTTON->value);
            });

            // 去掉前缀并返回去重结果
            $cleanedPermissions = array_map(function ($item) {
                return str_replace(PolicyPrefix::BUTTON->value, '', $item[2]); // 确保安全访问
            }, $permissions);

            // 去重并返回结果
            $resultValues = array_values(array_unique($cleanedPermissions));
        }

        // 排序并返回结果
        sort($resultValues);
        return $resultValues ?? [];
    }
}
