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

class ServerErrorHttpException extends BaseException
{
    /**
     * @var int
     */
    public int $statusCode = 500;

    /**
     * @var string
     */
    public string $errorMessage = '服务器内部错误，请稍后再试';
}
