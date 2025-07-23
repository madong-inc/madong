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

namespace core\enum\system;

/**
 *
 * 操作结果枚举
 * @author Mr.April
 * @since  1.0
 */
enum OperationResult:int
{
    case SUCCESS = 1;
    case FAILURE = 0;

    public function label(): string
    {
        return match($this) {
            self::SUCCESS => '成功',
            self::FAILURE => '失败'
        };
    }


    public function color():string{
         return match($this) {
            self::SUCCESS => 'success',
            self::FAILURE => 'error'
        };
    }
}
