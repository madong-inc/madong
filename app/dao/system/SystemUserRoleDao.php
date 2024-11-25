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

namespace app\dao\system;

use madong\basic\BaseDao;

class SystemUserRoleDao extends BaseDao
{

    protected function setModel(): string
    {
                return config('app.model_type') === 'laravelORM'
            ? 'app\\model\\la\\system\\SystemUserRole'
            : 'app\\model\\tp\\system\\SystemUserRole';
    }
}
