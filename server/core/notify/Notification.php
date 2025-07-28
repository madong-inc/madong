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

namespace core\notify;

use core\notify\enum\PushClientType;
use support\Container;

/**
 * - 消息通知服务门面类
 * - 提供静态方法访问通知服务的快捷方式，支持以下功能：
 * - 消息推送（支持单条/批量）
 * - 消息记录管理
 * - 多通道通知分发
 * @method static array sendAndRecord(PushClientType $clientType, string $businessModule, string $tenantId, string|int|array $userIds, string $event, array $data = [], array $messageData = [], ?string $socketId = null) 推送消息并记录（支持单条/批量）
 * @method static array batchSend(PushClientType $clientType, string $tenantId, array $messages) 批量推送不同业务消息
 *
 * @see \core\notify\NotificationService 底层服务实现类
 */
final class Notification
{
    private static ?NotificationService $instance = null;

    /**
     * 私有构造方法
     * 禁止外部实例化，强制使用静态调用方式
     */
    private function __construct()
    {
        // 禁止实例化
    }

    /**
     * 获取通知服务实例（单例模式）
     *
     * @return NotificationService 通知服务实例
     */
    private static function instance(): NotificationService
    {
        return self::$instance ??= Container::make(NotificationService::class);
    }

    /**
     * 静态方法调用代理
     *
     * @param string $method 调用的方法名
     * @param array  $args   方法参数
     *
     * @return mixed 方法调用结果
     */
    public static function __callStatic(string $method, array $args)
    {
        return self::instance()->$method(...$args);
    }




}
