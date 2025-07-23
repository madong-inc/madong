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

namespace core\exception\handler;


class AccessDeniedHttpException extends BaseException
{
    /**
     * @var int
     */
    public int $statusCode = 403;

    /**
     * @var string
     */
    public string $errorMessage = '当前操作没有权限执行，请检查您的账户权限';
}
