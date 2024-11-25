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
 * 定时任务
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemCrontab extends BaseLaORMModel
{

    protected $table = 'system_crontab';

    protected $primaryKey = 'id';

    /**
     *  通过ID获取最后执行记录
     *
     * @return mixed
     */
    public function getLogAttribute(): mixed
    {
        return SystemCrontabLog::getModel()->where(['crontab_id' => $this->id])
            ->orderBy('create_time', 'desc')
            ->first();
    }

    /**
     * 获取器-创建时间
     *
     * @param $value
     *
     * @return string
     */
    public function getCreateTimeAttribute($value): string
    {
        return getDateText($value);
        return date('Y-m-d H:i:s', $value); // 将时间戳格式化为日期时间字符串
    }

    /**
     * 获取器-last_running_time
     *
     * @param $value
     *
     * @return bool|string
     */
    public function getLastRunningTimeAttribute($value): bool|string
    {
        if (!$value) return '--';
        return getDateText($value);
    }

    /**
     * 获取器-rule_name
     *
     * @param $value
     *
     * @return string
     */
    public function getRuleNameAttribute($value): string
    {
        $rule_arr = json_decode($this->cycle_rule, true);
        if (empty($rule_arr)) return '错误';
        switch ($this->task_cycle) {
            case 1:
                $rule = "每天{$rule_arr['hour']}点{$rule_arr['minute']}分";
                break;
            case 2:
                $rule = "每小时第{$rule_arr['minute']}分";
                break;
            case 3:
                $rule = "{$rule_arr['hour']}小时第{$rule_arr['minute']}分";
                break;
            case 4:
                $rule = "{$rule_arr['minute']}分";
                break;
            case 5:
                $rule = "{$rule_arr['second']}秒";
                break;
            case 6:
                $week_arr = [0 => '周日', 1 => '周一', 2 => '周二', 3 => '周三', 4 => '周四', 5 => '周五', 6 => '周六'];
                $rule     = "每{$week_arr[$rule_arr['week']]}第{$rule_arr['hour']}点{$rule_arr['minute']}分";
                break;
            case 7:
                $rule = "每月{$rule_arr['day']}号{$rule_arr['hour']}点{$rule_arr['minute']}分";
                break;
            case 8:
                $rule = "每年{$rule_arr['month']}月{$rule_arr['day']}号{$rule_arr['hour']}点{$rule_arr['minute']}分";
                break;
            default:
                $rule = "任务规则不正确";
        }
        return $rule;
    }

    /**
     * 关联执行志记录表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SystemCrontabLog::class, 'crontab_id', 'id');
    }
}
