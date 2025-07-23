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

namespace core\casbin;

use Casbin\Enforcer;
use Casbin\Exceptions\CasbinException;
use Casbin\Log\Logger\DefaultLogger;
use Casbin\Model\Model;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use support\Container;
use core\casbin\watcher\RedisWatcher;
use core\casbin\traits\PermissionTrait;

class Permission
{

    use PermissionTrait;

    /** @var Enforcer[] $_manager */
    protected static array $_manager = [];

    /**
     * @desc driver
     *
     * @param string|null $driver
     *
     * @return Enforcer
     * @throws CasbinException
     */
    public static function driver(?string $driver = null): Enforcer
    {
        $driver = $driver ?? self::getDefaultDriver();
        $config = self::getConfig($driver);

        if (isset(static::$_manager[$driver])) {
            return static::$_manager[$driver];
        }

        $model = new Model();
        if ('file' == $config['model']['config_type']) {
            $model->loadModel($config['model']['config_file_path']);
        } elseif ('text' == $config['model']['config_type']) {
            $model->loadModel($config['model']['config_text']);
        }
        $logConfig = self::getConfig('log');
        $logger    = null;
        if (true === $logConfig['enabled']) {
            /** @var LoggerInterface $casbinLogger 创建一个 Monolog 日志记录器 */
            $casbinLogger = new Logger($logConfig['logger']);
            $casbinLogger->pushHandler(new StreamHandler($logConfig['path'], Logger::DEBUG));
            $logger = new DefaultLogger($casbinLogger);
        }
        static::$_manager[$driver] = new Enforcer($model, Container::make($config['adapter'], [$driver]), $logger, $logConfig['enabled']);

        $watcher = new RedisWatcher(config('redis.default'), $driver);
        static::$_manager[$driver]->setWatcher($watcher);
        $watcher->setUpdateCallback(function () use ($driver) {
            static::$_manager[$driver]->loadPolicy();
        });
        return static::$_manager[$driver];
    }

    /**
     * @desc: 获取所有驱动
     * @return Enforcer[]
     */
    public static function getAllDriver(): array
    {
        return static::$_manager;
    }

    /**
     * @desc: 默认驱动
     * @return mixed
     */
    public static function getDefaultDriver(): mixed
    {
        return self::getConfig('default');
    }

    /**
     * @desc: 获取驱动配置
     *
     * @param string|null $name    名称
     * @param null        $default 默认值
     *
     * @return mixed
     */
    public static function getConfig(string $name = null, $default = null): mixed
    {
        if (!is_null($name)) {
            return config('core.casbin.permission.' . $name, $default);
        }
        return config('core.casbin.permission.default');
    }

    /**
     * @desc: 静态调用
     *
     * @param string $method
     * @param        $arguments
     *
     * @return mixed
     * @throws CasbinException
     */
    public static function __callStatic(string $method, $arguments)
    {
        return self::driver()->{$method}(...$arguments);
    }

}
