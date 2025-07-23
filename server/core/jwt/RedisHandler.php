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

namespace core\jwt;

use core\jwt\ex\JwtCacheTokenException;
use support\Redis;

class RedisHandler
{
    /**
     * Generate and store a new token in Redis
     * - Clears any existing token for this user/client combination
     * - Stores the new token with specified TTL
     *
     * @param string $prefix Key prefix
     * @param string $client Client identifier (WEB/MOBILE)
     * @param string $userId User ID
     * @param int    $ttl    Time-to-live in seconds
     * @param string $token  JWT token to store
     */
    public static function generateToken(string $prefix, string $client, string $userId, int $ttl, string $token): void
    {
        $cacheKey = self::buildCacheKey($prefix, $client, $userId);
        self::clearExistingToken($cacheKey);
        Redis::setex($cacheKey, $ttl, $token);
    }

    /**
     * Refresh an existing token in Redis
     * - Maintains the original TTL if the key exists
     * - Otherwise uses the provided TTL
     *
     * @param string $prefix     Key prefix
     * @param string $client     Client identifier
     * @param string $userId     User ID
     * @param int    $defaultTtl Default TTL if key doesn't exist
     * @param string $token      JWT token to store
     */
    public static function refreshToken(string $prefix, string $client, string $userId, int $defaultTtl, string $token): void
    {
        $cacheKey = self::buildCacheKey($prefix, $client, $userId);
        $ttl      = Redis::exists($cacheKey) ? Redis::ttl($cacheKey) : $defaultTtl;
        Redis::setex($cacheKey, $ttl, $token);
    }

    /**
     * Verify the token matches what's stored in Redis
     *
     * @param string $prefix Key prefix
     * @param string $client Client identifier
     * @param string $userId User ID
     * @param string $token  Token to verify
     *
     * @return bool
     * @throws JwtCacheTokenException If token is invalid or expired
     */
    public static function verifyToken(string $prefix, string $client, string $userId, string $token): bool
    {
        $cacheKey = self::buildCacheKey($prefix, $client, $userId);

        if (!Redis::exists($cacheKey)) {
            throw new JwtCacheTokenException('This account has been logged in from another device');
        }

        if (Redis::get($cacheKey) !== $token) {
            throw new JwtCacheTokenException('Authentication session expired, please login again');
        }

        return true;
    }

    /**
     * Clear/delete a token from Redis
     *
     * @param string $prefix Key prefix
     * @param string $client Client identifier
     * @param string $userId User ID
     *
     * @return bool Always returns true
     */
    public static function clearToken(string $prefix, string $client, string $userId): bool
    {
        $cacheKey = self::buildCacheKey($prefix, $client, $userId);
        self::clearExistingToken($cacheKey);
        return true;
    }

    /**
     * Adding a token leads to a blacklist
     *
     * @param string $prefix          Redis键前缀
     * @param string $token           要加入黑名单的Token（支持原始Token或已MD5哈希的Token）
     * @param int    $exp             过期时间(秒)
     * @param bool   $isAlreadyHashed 标记Token是否已经是MD5格式
     *
     * @return bool
     */
    public static function addToBlacklist(
        string $prefix,
        string $token,
        int    $exp,
        bool   $isAlreadyHashed = false
    ): bool
    {
        $key = $prefix . ($isAlreadyHashed ? $token : md5($token));
        Redis::setEx($key, $exp, '1');
        return true;
    }

    /**
     * Check if the token has a black name
     *
     * @param string $prefix
     * @param string $token
     *
     * @return bool
     */
    public static function isInBlacklist(string $prefix, string $token): bool
    {
        $key = $prefix . md5($token);
        return (bool)Redis::get($key);
    }

    /**
     * Build consistent cache key
     *
     * @param string $prefix
     * @param string $client
     * @param string $userId
     *
     * @return string
     */
    private static function buildCacheKey(string $prefix, string $client, string $userId): string
    {
        return sprintf('%s%s:%s', $prefix, $client, $userId);
    }

    /**
     * Safely clear existing token if it exists
     *
     * @param string $cacheKey
     */
    private static function clearExistingToken(string $cacheKey): void
    {
        // Using direct key access instead of KEYS command for better performance
        if (Redis::exists($cacheKey)) {
            Redis::del($cacheKey);
        }
    }
}