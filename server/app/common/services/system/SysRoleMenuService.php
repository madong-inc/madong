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

use app\common\dao\system\SysRoleMenuDao;
use core\exception\handler\AdminException;
use core\abstract\BaseService;
use support\Container;

/**
 * @method getColumn(int[]|string[] $array, string $string)
 */
class SysRoleMenuService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysRoleMenuDao::class);
    }

    /**
     * 更新设置角色权限
     *
     * @param $data
     */
    public function save($data): void
    {
        try {
            $this->transaction(function () use ($data) {
                $roleId         = $data['role_id'] ?? '';
                $newPermissions = $data['menu_id'] ?? [];
                if (empty($roleId)) {
                    throw new AdminException('参数错误缺少role_id', -1);
                } // 获取当前权限
                $currentPermissions = $this->getColumn(['role_id' => $roleId], 'menu_id');

                // 计算需要添加和删除的权限
                $roleMenuIdsToAdd    = array_diff($newPermissions, $currentPermissions);
                $roleMenuIdsToRemove = array_diff($currentPermissions, $newPermissions);

                // 批量删除权限
                if (!empty($roleMenuIdsToRemove)) {
                    $this->dao->delete([
                        ['menu_id', 'in', $roleMenuIdsToRemove],
                        ['role_id', '=', $roleId],
                    ]);
                }

                // 批量添加权限
                if (!empty($roleMenuIdsToAdd)) {
                    $data = array_map(function ($menuId) use ($roleId) {
                        return ['role_id' => $roleId, 'menu_id' => $menuId];
                    }, $roleMenuIdsToAdd);

                    $this->dao->saveAll($data);
                }
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }
}
