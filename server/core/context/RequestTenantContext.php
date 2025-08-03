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

use core\enum\platform\IsolationMode;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use support\Db;

class RequestTenantContext implements RequestTenantContextInterface
{
    private const ALLOWED_KEYS = [
        'tenant_id',
        'code',
        'isolation_mode',
        'database_connection',
        'expiration_time',
    ];

    private \ArrayObject $storage;

    public function __construct()
    {
        $this->storage = new \ArrayObject();
    }

    /**
     * 兼容历史版本
     *
     * @param string|int  $tenantId
     * @param string|null $code
     * @param string|int  $isolationMode
     * @param string|null $databaseConnection
     * @param int|null    $expirationTime
     */
    public function setContext(
        string|int $tenantId,
        ?string    $code,
        string|int $isolationMode,
        ?string    $databaseConnection = null,
        ?int       $expirationTime = null
    ): void
    {
        $this->initContext($tenantId, $code, $isolationMode, $databaseConnection, $expirationTime);
    }

    // 新增销毁方法
    public function destroy(): void
    {
        $this->reset();
    }

    public function get(?string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->storage->getArrayCopy();
        }

        return $this->storage->offsetExists($name)
            ? $this->storage->offsetGet($name)
            : $default;
    }

    public function set(string $name, mixed $value): void
    {
        if (!in_array($name, self::ALLOWED_KEYS, true)) {
            throw new InvalidArgumentException("Invalid context key: {$name}");
        }

        if ($name === 'isolation_mode') {
            $this->validateIsolationMode($value);
        }

        $this->storage->offsetSet($name, $value);
    }

    public function has(string $name): bool
    {
        return $this->storage->offsetExists($name);
    }

    public function reset(): void
    {
        $this->storage = new \ArrayObject();
    }

    public function initContext(
        string|int $tenantId,
        ?string    $code,
        string|int $isolationMode,
        ?string    $databaseConnection = null,
        ?int       $expirationTime = null
    ): void
    {
        $this->set('tenant_id', $tenantId);
        $this->set('code', $code);
        $this->set('isolation_mode', $isolationMode);

        if ($isolationMode === IsolationMode::LIBRARY_ISOLATION->value) {
            if ($databaseConnection === null) {
                throw new InvalidArgumentException(
                    'Database connection must be set in library isolation mode'
                );
            }
            $this->set('database_connection', $databaseConnection);
        } else {
            $this->set('database_connection', config('database.default'));
        }

        $this->set('expiration_time', $expirationTime);
    }

    public function getTenantId(): string|int|null
    {
        return $this->get('tenant_id');
    }

    public function getTenantCode(): ?string
    {
        return $this->get('code');
    }

    public function getIsolationMode(): string|int
    {
        return $this->get('isolation_mode');
    }

    public function getDatabaseConnection(): ?string
    {
        return $this->get('database_connection');
    }

    public function getExpirationTime(): ?int
    {
        return $this->get('expiration_time');
    }

    public function isFieldIsolation(): bool
    {
        return $this->get('isolation_mode') === IsolationMode::FIELD_ISOLATION->value;
    }

    public function isLibraryIsolation(): bool
    {
        return $this->get('isolation_mode') === IsolationMode::LIBRARY_ISOLATION->value;
    }

    public function isExpired(): bool
    {
        $expirationTime = $this->getExpirationTime();
        return $expirationTime !== null && time() > $expirationTime;
    }

    /**
     * 获取数据库连接
     *
     * @return Connection
     */
    public function db(): Connection
    {
        return Db::connection($this->getDatabaseConnection());
    }

    private function validateIsolationMode(string|int $mode): void
    {
        if (!in_array($mode, IsolationMode::valuesArray(), true)) {
            throw new InvalidArgumentException("Invalid isolation mode: {$mode}");
        }
    }
}

