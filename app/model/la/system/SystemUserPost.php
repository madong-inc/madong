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
 * 用户管理岗位-中间模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemUserPost extends Pivot
{
    protected $table = 'system_user_post';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
