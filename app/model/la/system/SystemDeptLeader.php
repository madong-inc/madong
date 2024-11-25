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


use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 关联模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemDeptLeader extends Pivot
{
    protected $table = 'system_dept_leader';


}
