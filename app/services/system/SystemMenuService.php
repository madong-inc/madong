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

namespace app\services\system;

use app\dao\system\SystemMenuDao;
use madong\basic\BaseService;
use madong\utils\Tree;
use support\Container;
use support\Request;

/**
 * @method save(array $data)
 * @method getColumn(array $map, string $string)
 */
class SystemMenuService extends BaseService
{
    public function __construct()
    {
        $this->dao = Container::make(SystemMenuDao::class);
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
