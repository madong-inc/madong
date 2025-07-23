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

namespace core\enum\platform;

use core\enum\IEnum;

/**
 * 租户模式
 *
 * @author Mr.April
 * @since  1.0
 */
enum IsolationMode: int implements IEnum
{

    case FIELD_ISOLATION = 1;  // 字段隔离
    case LIBRARY_ISOLATION = 2; // 库隔离

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::FIELD_ISOLATION => '字段隔离',
            self::LIBRARY_ISOLATION => '分库隔离',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FIELD_ISOLATION => '#2db7f5',
            self::LIBRARY_ISOLATION => '#87d068'
        };
    }

    /**
     * 枚举值的数组（仅 value）
     *
     * @return array<int>
     */
    public static function valuesArray(): array
    {
        return [self::FIELD_ISOLATION->value, self::LIBRARY_ISOLATION->value];
    }
}
