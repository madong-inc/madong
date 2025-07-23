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

namespace core\casbin\model;

use app\common\model\system\SysRole;
use app\common\model\system\SysRoleCasbin;
use core\abstract\BaseModel;

/**
 * RuleModel Model
 *
 * @author Mr.April
 * @since  1.0
 */
class RuleModel extends BaseModel
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = ['ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5'];

    protected $casts = [
        'v0' => 'string',
        'v1' => 'string',
        'v2' => 'string',
        'v3' => 'string',
        'v4' => 'string',
        'v5' => 'string',
    ];

    /** @var string|null $driver */
    protected ?string $driver;

    protected $table = 'sys_casbin_rule';

    /**
     * 架构函数
     *
     * @param array       $data
     * @param string|null $driver
     */
    public function __construct(array $data = [], ?string $driver = null)
    {
        //注释不使用配置使用默认链接要不框架无法使用事务
//        $this->driver = $driver;
//        $connection   = $this->config('database.connection') ?: config('database.default');
//        $this->setConnection($connection);
//        $this->setTable($this->config('database.rules_table'));
        parent::__construct($data);
    }

    /**
     * Gets config value by key.
     *
     * @param string|null $key
     * @param null        $default
     *
     * @return mixed
     */
    protected function config(string $key = null, $default = null): mixed
    {
        $driver = $this->driver ?? config('core.casbin.permission.default');
        return config('core.casbin.permission.' . $driver . '.' . $key, $default);
    }

    /**
     * 关联-角色表
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SysRole::class, SysRoleCasbin::class, 'role_casbin_id', 'role_id', 'v1', 'id');
    }

    /**
     * 默认链接
     */
    protected function initialize()
    {
        $this->connection = config('database.default');
    }
}
