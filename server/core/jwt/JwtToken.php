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

use core\jwt\ex\JwtConfigException;
use core\jwt\ex\JwtRefreshTokenExpiredException;
use core\jwt\ex\JwtTokenException;
use core\jwt\ex\JwtTokenExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Throwable;
use UnexpectedValueException;

class JwtToken
{
    private const ACCESS_TOKEN = 1;
    private const REFRESH_TOKEN = 2;

    public const TOKEN_CLIENT_WEB = 'WEB';
    public const TOKEN_CLIENT_MOBILE = 'MOBILE';

    /**
     * Get current user ID
     *
     * @return mixed
     * @throws JwtTokenException
     */
    public static function getCurrentId(): mixed
    {
        return self::getExtendVal('id') ?? 0;
    }

    /**
     * Get current user info
     *
     * @return array
     * @throws JwtTokenException
     */
    public static function getUser(): array
    {
        $config = self::getConfig();
        return is_callable($config['user_model'])
            ? $config['user_model'](self::getCurrentId()) ?? []
            : [];
    }

    /**
     * Get extended token value
     *
     * @param string $key
     *
     * @return mixed|string
     * @throws JwtTokenException
     */
    public static function getExtendVal(string $key)
    {
        return self::getTokenExtend()[$key] ?? '';
    }

    /**
     * Get all extended token data
     *
     * @return array
     * @throws JwtTokenException
     */
    public static function getExtend(): array
    {
        return self::getTokenExtend();
    }

    /**
     * Refresh token
     *
     * @return array
     * @throws JwtTokenException
     */
    public static function refreshToken(): array
    {
        $token  = self::getTokenFromHeaders();
        $config = self::getConfig();

        try {
            $extend = self::verifyToken($token, self::REFRESH_TOKEN);
        } catch (Throwable $e) {
            throw self::mapException($e, true);
        }

        $payload   = self::generatePayload($config, $extend['extend']);
        $secretKey = self::getPrivateKey($config);

        $extend['exp'] = time() + $config['access_exp'];
        $newToken      = [
            'access_token' => self::makeToken($extend, $secretKey, $config['algorithms']),
        ];

        if (!($config['refresh_disable'] ?? false)) {
            $refreshSecretKey          = self::getPrivateKey($config, self::REFRESH_TOKEN);
            $payload['exp']            = time() + $config['refresh_exp'];
            $newToken['refresh_token'] = self::makeToken($payload['refreshPayload'], $refreshSecretKey, $config['algorithms']);
        }

        if ($config['is_single_device']) {
            $client = $extend['extend']['client'] ?? self::TOKEN_CLIENT_WEB;
            $id     = (string)$extend['extend']['id'];

            RedisHandler::generateToken(
                $config['cache_token_pre'],
                $client,
                $id,
                $config['access_exp'],
                $newToken['access_token']
            );

            if (isset($newToken['refresh_token'])) {
                RedisHandler::refreshToken(
                    $config['cache_refresh_token_pre'],
                    $client,
                    $id,
                    $config['refresh_exp'],
                    $newToken['refresh_token']
                );
            }
        }

        return $newToken;
    }

    /**
     * Generate new token
     *
     * @param array $extend
     *
     * @return array
     * @throws JwtTokenException
     */
    public static function generateToken(array $extend): array
    {
        if (!isset($extend['id'])) {
            throw new JwtTokenException('Missing required field: id');
        }

        $config                = self::getConfig();
        $config['access_exp']  = $extend['access_exp'] ?? $config['access_exp'];
        $config['refresh_exp'] = $extend['refresh_exp'] ?? $config['refresh_exp'];

        $payload   = self::generatePayload($config, $extend);
        $secretKey = self::getPrivateKey($config);

        $token = [
            'token_type'   => 'Bearer',
            'expires_in'   => $config['access_exp'],
            'access_token' => self::makeToken($payload['accessPayload'], $secretKey, $config['algorithms']),
        ];

        if (!($config['refresh_disable'] ?? false)) {
            $refreshSecretKey       = self::getPrivateKey($config, self::REFRESH_TOKEN);
            $token['refresh_token'] = self::makeToken($payload['refreshPayload'], $refreshSecretKey, $config['algorithms']);
        }

        if ($config['is_single_device']) {
            $client = $extend['client'] ?? self::TOKEN_CLIENT_WEB;
            $id     = (string)$extend['id'];

            RedisHandler::generateToken(
                $config['cache_token_pre'],
                $client,
                $id,
                $config['access_exp'],
                $token['access_token']
            );

            if (isset($token['refresh_token']) && isset($config['cache_refresh_token_pre'])) {
                RedisHandler::generateToken(
                    $config['cache_refresh_token_pre'],
                    $client,
                    $id,
                    $config['refresh_exp'],
                    $token['refresh_token']
                );
            }
        }

        return $token;
    }

    /**
     * Verify token
     *
     * @param int         $tokenType
     * @param string|null $token
     *
     * @return array
     * @throws JwtTokenException
     */
    public static function verify(int $tokenType = self::ACCESS_TOKEN, string $token = null): array
    {
        $token = $token ?? self::getTokenFromHeaders();
        try {
            return self::verifyToken($token, $tokenType);
        } catch (Throwable $e) {
            throw self::mapException($e);
        }
    }

    /**
     * Get token expiration time
     *
     * @param int $tokenType
     *
     * @return int
     * @throws JwtTokenException
     */
    public static function getTokenExp(int $tokenType = self::ACCESS_TOKEN): int
    {
        return (int)self::verify($tokenType)['exp'] - time();
    }

    /**
     * Clear/invalidate token
     *
     * @param string $client
     *
     * @return bool
     * @throws JwtTokenException
     */
    public static function clear(string $client = self::TOKEN_CLIENT_WEB): bool
    {
        $config = self::getConfig();
        if (!$config['is_single_device']) {
            return true;
        }
        $id           = (string)self::getCurrentId();
        $clearRefresh = RedisHandler::clearToken($config['cache_refresh_token_pre'], $client, $id);
        $clearAccess  = RedisHandler::clearToken($config['cache_token_pre'], $client, $id);

        return $clearAccess && $clearRefresh;
    }

    /**
     * Add the Token to the blacklist
     *
     * @param string $token
     * @param bool   $isAlreadyHashed
     *
     * @return bool
     */
    public static function addToBlacklist(string $token, bool $isAlreadyHashed = false): bool
    {
        $config = self::getConfig();
        if (!$config['blacklist_enabled']) {
            return false;
        }

        RedisHandler::addToBlacklist($config['cache_blacklist_pre'] ?? '', $token, $config['access_exp'] ?? 7200, $isAlreadyHashed);
        return true;
    }

    /**
     * Get token from headers
     *
     * @return string
     * @throws JwtTokenException
     */
    private static function getTokenFromHeaders(): string
    {
        $authorization = request()->header(config('core.jwt.app.token_name', 'Authorization'));
        if (!$authorization) {
            $authorization = request()->get('token');
        }

        if (!$authorization || $authorization === 'undefined') {
            throw new JwtTokenException('Missing authorization header');
        }

        if (self::REFRESH_TOKEN != substr_count($authorization, '.')) {
            throw new JwtTokenException('Invalid authorization format');
        }

        if (count(explode(' ', $authorization)) !== 2) {
            throw new JwtTokenException('Authorization header must follow Bearer scheme');
        }

        [$type, $token] = explode(' ', $authorization);

        if ($type !== 'Bearer') {
            throw new JwtTokenException('Authorization type must be Bearer');
        }

        if (!$token || $token === 'undefined') {
            throw new JwtTokenException('Token is missing');
        }

        return $token;
    }

    /**
     * Verify token and return payload
     *
     * @param string $token
     * @param int    $tokenType
     *
     * @return array
     */
    private static function verifyToken(string $token, int $tokenType): array
    {
        $config = self::getConfig();

        // 检查黑名单
        if (self::isInBlacklist($token)) {
            throw new JwtTokenException('Token has been invalidated');
        }
        $publicKey   = self::getPublicKey($config['algorithms'], $tokenType);
        JWT::$leeway = $config['leeway'];
        $decoded     = JWT::decode($token, new Key($publicKey, $config['algorithms']));
        $payload     = json_decode(json_encode($decoded), true);

        if ($config['is_single_device']) {
            $cachePrefix = $tokenType === self::REFRESH_TOKEN
                ? $config['cache_refresh_token_pre']
                : $config['cache_token_pre'];

            $client = $payload['extend']['client'] ?? self::TOKEN_CLIENT_WEB;
            RedisHandler::verifyToken($cachePrefix, $client, (string)$payload['extend']['id'], $token);
        }

        return $payload;
    }

    /**
     * Generate JWT token
     *
     * @param array  $payload
     * @param string $secretKey
     * @param string $algorithm
     *
     * @return string
     */
    private static function makeToken(array $payload, string $secretKey, string $algorithm): string
    {
        return JWT::encode($payload, $secretKey, $algorithm);
    }

    /**
     * Generate token payload
     *
     * @param array $config
     * @param array $extend
     *
     * @return array
     */
    private static function generatePayload(array $config, array $extend): array
    {
        $basePayload = [
            'iss'    => $config['iss'],
            'aud'    => $config['iss'],
            'iat'    => time(),
            'nbf'    => time() + ($config['nbf'] ?? 0),
            'exp'    => time() + $config['access_exp'],
            'extend' => $extend,
        ];

        return [
            'accessPayload'  => $basePayload,
            'refreshPayload' => array_merge($basePayload, [
                'exp' => time() + $config['refresh_exp'],
            ]),
        ];
    }

    /**
     * Get public key based on algorithm
     *
     * @param string $algorithm
     * @param int    $tokenType
     *
     * @return string
     * @throws JwtConfigException
     */
    private static function getPublicKey(string $algorithm, int $tokenType = self::ACCESS_TOKEN): string
    {
        $config = self::getConfig();

        return match ($algorithm) {
            'HS256' => $tokenType === self::ACCESS_TOKEN
                ? $config['access_secret_key']
                : $config['refresh_secret_key'],
            'RS256', 'RS512' => $tokenType === self::ACCESS_TOKEN
                ? $config['access_public_key']
                : $config['refresh_public_key'],
            default => $config['access_secret_key'],
        };
    }

    /**
     * Get private key based on algorithm
     *
     * @param array $config
     * @param int   $tokenType
     *
     * @return string
     */
    private static function getPrivateKey(array $config, int $tokenType = self::ACCESS_TOKEN): string
    {
        return match ($config['algorithms']) {
            'HS256' => $tokenType === self::ACCESS_TOKEN
                ? $config['access_secret_key']
                : $config['refresh_secret_key'],
            'RS256', 'RS512' => $tokenType === self::ACCESS_TOKEN
                ? $config['access_private_key']
                : $config['refresh_private_key'],
            default => $config['access_secret_key'],
        };
    }

    /**
     * Get JWT configuration
     *
     * @return array
     * @throws JwtConfigException
     */
    private static function getConfig(): array
    {
        $config = config('core.jwt.app.jwt');
        if (empty($config)) {
            throw new JwtConfigException('JWT configuration not found');
        }
        return $config;
    }

    /**
     * Get token extended data
     *
     * @return array
     * @throws JwtTokenException
     */
    private static function getTokenExtend(): array
    {

        return (array)self::verify()['extend'];
    }

    /**
     * Check whether the Token is on the blacklist
     */
    private static function isInBlacklist(string $token): bool
    {
        $config = self::getConfig();
        if (!$config['blacklist_enabled']) {
            return false;
        }
        return (bool)RedisHandler::isInBlacklist($config['cache_blacklist_pre'], $token);
    }

    /**
     * Map exceptions to JwtTokenException
     *
     * @param Throwable $e
     * @param bool      $isRefresh
     *
     * @return \core\jwt\ex\JwtTokenException|\core\jwt\ex\JwtTokenExpiredException|\core\jwt\ex\JwtRefreshTokenExpiredException
     */
    private static function mapException(Throwable $e, bool $isRefresh = false): JwtTokenException|JwtTokenExpiredException|JwtRefreshTokenExpiredException
    {
        $prefix = $isRefresh ? 'Refresh token: ' : '';

        if ($e instanceof SignatureInvalidException) {
            return new JwtTokenException($prefix . 'Invalid token signature');
        } elseif ($e instanceof BeforeValidException) {
            return new JwtTokenException($prefix . 'Token not yet valid');
        } elseif ($e instanceof ExpiredException) {
            return $isRefresh
                ? new JwtRefreshTokenExpiredException($prefix . 'Session expired, please login again')
                : new JwtTokenExpiredException($prefix . 'Session expired, please login again');
        } elseif ($e instanceof UnexpectedValueException) {
            return new JwtTokenException($prefix . 'Invalid token data');
        } else {
            return new JwtTokenException($prefix . $e->getMessage());
        }
    }

}
