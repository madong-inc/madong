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

namespace core\context;

use support\Container;

/**
 * 租户上下文管理类
 * 实现 ContextInterface 接口，提供统一的上下文管理能力，
 * 同时支持动态管理租户相关的上下文信息，如租户ID、隔离模式和数据库连接配置。
 * @method static mixed get(?string $name = null, mixed $default = null)
 * @method static void set(string $name, mixed $value)
 * @method static bool has(string $name)
 * @method static void reset(?array $data = null)
 * @method static void destroy()
 * @method static void setContext(string|int $tenantId, ?string $code, string|int $isolationMode, ?string $connect = null, ?int $expirationTime = null)
 * @method static void initContext(string|int $tenantId, ?string $code, string|int $isolationMode, ?string $connect = null, ?int $expirationTime = null)
 * @method static ?string getTenantId()
 * @method static ?string getTenantCode()
 * @method static string|int getIsolationMode()
 * @method static ?string getDatabaseConnection()
 * @method static bool isFieldIsolation()
 * @method static bool isLibraryIsolation()
 * @method static ?int getExpirationTime()
 * @method static bool isExpired()
 * @method static getTenantId()
 */
class TenantContext
{
    private static ?RequestTenantContextInterface $instance = null;

    private static function instance(): RequestTenantContextInterface
    {

        return self::$instance ??= Container::get(RequestTenantContextInterface::class);
    }

    public static function __callStatic(string $method, array $args)
    {
        return self::instance()->$method(...$args);
    }

    public static function clear(): void
    {
        self::instance()->reset();
    }

}
