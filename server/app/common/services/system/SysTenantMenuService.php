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


use app\common\dao\system\SysTenantMenuDao;
use core\abstract\BaseService;
use support\Container;

/**
 * @method save(array $data)
 */
class SysTenantMenuService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysTenantMenuDao::class);
    }

    /**
     * 通过角色筛选菜单ID集合并去重
     *
     * @param array $roleData
     *
     * @return array
     */
    public function filterMenuIds(array &$roleData): array
    {
        $idBundle = [];
        foreach ($roleData as $val) {
            foreach ($val['menus'] as $menu) {
                $idBundle[] = $menu['id'];
            }
        }
        unset($roleData);
        return array_unique($idBundle);
    }
}
