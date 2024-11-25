<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.cn
 */
namespace app\model\la\system;

use madong\basic\BaseLaORMModel;

class SystemOperateLog extends BaseLaORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected  $primaryKey = 'id';

    protected  $table = 'system_operate_log';

}
