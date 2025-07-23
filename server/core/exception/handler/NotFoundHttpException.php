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

class NotFoundHttpException extends BaseException
{
    /**
     * @var int
     */
    public int $statusCode = 404;

    /**
     * @var string
     */
    public string $errorMessage = '请求的资源不存在或已被删除';
}
