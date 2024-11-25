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

/**
 *定时任务日志
 */
class SystemCrontabLog extends BaseLaORMModel
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_crontab_log';

    /**
     * The primary key associated with the table
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 获取 create_time 属性的访问器。
     *
     * @param $value
     *
     * @return string
     */
    public function getCreateTimeAttr($value): string
    {
        return date('Y-m-d H:i:s', $value); // 将时间戳格式化为日期时间字符串
    }

    public function getRunningTimeAttr($value): string
    {
        return number_format($value, 2) . 'ms'; // 将时间戳格式化为日期时间字符串
    }

}




















