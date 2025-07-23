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

namespace app\common\services\system;

use core\abstract\BaseService;
use core\casbin\Permission;
use core\enum\system\PolicyPrefix;
use core\exception\handler\AdminException;

class SysCasbinService extends BaseService
{

    public function __construct()
    {
        $this->dao = null;
    }

    /**
     * 更新casbin 策略
     *
     * @param array $currentPermissions
     * @param array $incomingPolicies
     */
    public function updateCasbinPolicies(array $currentPermissions, array $incomingPolicies): void
    {
        // 计算需要删除的权限：当前有但传入没有的
        $permissionsToDelete = array_filter($currentPermissions, function ($currentPolicy) use ($incomingPolicies) {
            foreach ($incomingPolicies as $incomingPolicy) {
                if (
                    (string)$currentPolicy[0] === (string)$incomingPolicy[0] &&
                    (string)$currentPolicy[1] === (string)$incomingPolicy[1] &&
                    (string)$currentPolicy[5] === (string)$incomingPolicy[5]
                ) {
                    return false; // 找到了匹配的，当前权限不需要删除
                }
            }
            return true; // 没有找到匹配的，当前权限需要删除
        });

        // 计算需要添加的权限：传入有但当前没有的
        $permissionsToAdd = array_filter($incomingPolicies, function ($incomingPolicy) use ($currentPermissions) {
            foreach ($currentPermissions as $currentPolicy) {
                if (
                    (string)$incomingPolicy[0] === (string)$currentPolicy[0] &&
                    (string)$incomingPolicy[1] === (string)$currentPolicy[1] &&
                    (string)$incomingPolicy[5] === (string)$currentPolicy[5]
                ) {
                    return false; // 找到了匹配的，传入权限不需要添加
                }
            }
            return true; // 没有找到匹配的，传入权限需要添加
        });

        // 删除不需要的权限
        foreach ($permissionsToDelete as $policy) {
            Permission::removeFilteredPolicy(0, $policy[0], $policy[1], '', '', '', $policy[5]);
        }

        // 添加新的权限
        foreach ($permissionsToAdd as $policy) {
            $res = Permission::addPolicy(...$policy);
            if (!$res) {
                throw new AdminException('添加失败：' . $policy[5]);
            }
        }
    }

    /**
     * 根据资源类型获取标准化权限标识符
     *
     * @param array $item 资源数据数组
     *
     * @return string 标准化权限标识符
     */
    public function resolvePermissionIdentifier(array $item): string
    {
        return match ((int)$item['type']) {
            1, 2 => PolicyPrefix::MENU->value . $item['path'],  // 菜单项资源
            3 => PolicyPrefix::BUTTON->value . $item['code'],   // 按钮代码资源
            4 => PolicyPrefix::ROUTE->value . $item['path'],     // API路由资源
            default => '*'
        };
    }

}
