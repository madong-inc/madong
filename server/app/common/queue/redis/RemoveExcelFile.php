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

namespace app\common\queue\redis;

use Webman\RedisQueue\Consumer;

/**
 * 删除导出的残留excel文件
 *
 * @author Mr.April
 * @since  1.0
 */
class RemoveExcelFile implements Consumer
{

    // 要消费的队列名
    public string $queue = 'remove-excel-file';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public string $connection = 'default';

    public function consume($data)
    {
        $filePath = $data['file_path'] ?? '';
        $this->deleteFile(runtime_path() . $filePath);
    }

    /**
     * 删除指定路径的文件
     *
     * @param string $filePath 文件路径
     *
     * @return bool 返回删除结果
     */
    public function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}
