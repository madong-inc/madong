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

use app\common\dao\system\SysDeptLeaderDao;
use core\abstract\BaseService;
use support\Container;

/**
 * @author Mr.April
 * @since  1.0
 */
class SysDeptLeaderService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysDeptLeaderDao::class);
    }

}
