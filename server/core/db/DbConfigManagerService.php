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

namespace core\db;

use PDO;
use Webman\Exception\BusinessException;

class DbConfigManagerService
{
    private const CACHE_KEY = 'db_config_cache';
    private const CACHE_TTL = 300; // 5分钟缓存

    private const TABLE_NAME = 'ma_mt_db_setting';//多数据源表

    // 静态调用入口
    public static function getConfig(array $base = [], string $defaultKey = 'mysql', bool $isDefaultAppend = false): array
    {
        try {
            $installLockPath = base_path() . '/install.lock';
            $envFilePath     = base_path() . '/.env';
            if (!file_exists($installLockPath) || !file_exists($envFilePath)) {
                // 系统未安装，返回空数组
                return [];
            }

            if (empty($base)) {
                throw new BusinessException('Missing database name in base config');
            }
            $config = self::queryDbConfig($base);
            if ($isDefaultAppend && !empty($defaultKey)) {
                $config[$defaultKey] = $base;
            }
            //无效缓存配置程序启动载入后不在载入
//            Cache::set(self::CACHE_KEY, $config, self::CACHE_TTL);
            return $config;
        } catch
        (\PDOException $e) {
            throw new BusinessException('数据库配置获取失败: ' . $e->getMessage());
        }
    }

    private static function queryDbConfig(array $base): array
    {
        $config = [];
        $dsn    = sprintf('mysql:host=%s;port=%d;dbname=%s',
            $base['host'],
            $base['port'],
            $base['database']
        );
        $pdo    = new PDO(
            $dsn,
            $base['username'],
            $base['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'",
            ]
        );

        $table = self::TABLE_NAME;
        $sql   = "SELECT `database`, `username`, `password`, `prefix`, `driver`, `host`,`port` 
                FROM `{$table}`
                WHERE enabled = 1";
        $stmt  = $pdo->prepare($sql);
        $stmt->execute();
        $tenants = $stmt->fetchAll();

        foreach ($tenants as $tenant) {
            $config[$tenant['database']] = [
                'driver'      => $tenant['driver'] ?? 'mysql',
                'host'        => $tenant['host'] ?? '127.0.0.1',
                'port'        => (int)$tenant['port'] ?? 3306,
                'database'    => $tenant['database'],
                'username'    => $tenant['username'],
                'password'    => $tenant['password'],
                'charset'     => 'utf8mb4',
                'unix_socket' => $base['unix_socket'] ?? '',
                'collation'   => 'utf8mb4_general_ci',
                'prefix'      => $tenant['prefix'] ?? '',
                'strict'      => $base['strict'] ?? false,
                'engine'      => null,
                'pool'        => $base['pool'] ?? [],
            ];
        }
        $pdo = null;
        return $config;
    }
}
