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

namespace core\casbin\watcher;


use Casbin\Persist\Watcher;
use Closure;
use Workerman\Redis\Client;

class RedisWatcher implements Watcher
{
    private Closure $callback;

    private Client $pubRedis;

    private Client $subRedis;

    private string $channel;
    /**
     * The config of Watcher.
     *
     * @param array $config
     * [
     *     'host' => '127.0.0.1',
     *     'password' => '',
     *     'port' => 6379,
     *     'database' => 0,
     *     'channel' => '/casbin',
     * ]
     */
    public function __construct(array $config, string $driver)
    {
        $this->pubRedis = $this->createRedisClient($config);
        $this->subRedis = $this->createRedisClient($config);
        $this->channel = ($config['channel'] ?? '/casbin') . '/' . $driver;

        $this->subRedis->subscribe([$this->channel], function ($channel, $message) {
            if ($this->callback) {
                call_user_func($this->callback);
            }
        });
    }

    /**
     * Sets the callback function that the watcher will call when the policy in DB has been changed by other instances.
     * A classic callback is loadPolicy() method of Enforcer class.
     *
     * @param Closure $func
     */
    public function setUpdateCallback(Closure $func): void
    {
        $this->callback = $func;
    }

    /**
     * Update calls the update callback of other instances to synchronize their policy.
     * It is usually called after changing the policy in DB, like savePolicy() method of Enforcer class,
     * addPolicy(), removePolicy(), etc.
     */
    public function update(): void
    {
        $this->pubRedis->publish($this->channel, 'casbin rules updated');
    }

    /**
     * Close stops and releases the watcher, the callback function will not be called any more.
     */
    public function close(): void
    {
        $this->pubRedis->close();
        $this->subRedis->close();
    }

    /**
     * Create redis client
     *
     * @param array $config
     * @return Client
     */
    private function createRedisClient(array $config): Client
    {
        $redis = new Client('redis://' . ($config['host'] ?? '127.0.0.1') . ':' . ($config['port'] ?? 6379));
        $redis->auth($config['password'] ?? '');

        return $redis;
    }
}
