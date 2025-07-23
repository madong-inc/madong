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

interface RequestTenantContextInterface
{
    public function get(?string $name = null, mixed $default = null): mixed;
    public function set(string $name, mixed $value): void;
    public function has(string $name): bool;
    public function reset(): void;

    public function initContext(
        string|int $tenantId,
        ?string $code,
        string|int $isolationMode,
        ?string $databaseConnection = null,
        ?int $expirationTime = null
    ): void;

    public function getTenantId(): string|int|null;
    public function getTenantCode(): ?string;
    public function getIsolationMode(): string|int;
    public function getDatabaseConnection(): ?string;
    public function getExpirationTime(): ?int;

    public function isFieldIsolation(): bool;
    public function isLibraryIsolation(): bool;
    public function isExpired(): bool;
}

