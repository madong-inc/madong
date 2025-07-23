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

namespace app\common\dao\system;

use core\casbin\model\RuleModel;

/**
 *
 * 权限策略DAO
 * @author Mr.April
 * @since  1.0
 */
class SysCasbinDao extends BaseDao
{
    protected function setModel(): string
    {
        return RuleModel::class;
    }

}
