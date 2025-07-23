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


class TooManyRequestsHttpException extends BaseException
{
    /**
     * @var int
     */
    public int $statusCode = 429;

    /**
     * @var string
     */
    public string $errorMessage = '请求过于频繁，请稍后再试';
}
