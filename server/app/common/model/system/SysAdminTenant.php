<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.cn
 */

namespace app\common\model\system;

use app\common\model\platform\Tenant;
use app\common\scopes\global\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use core\abstract\BaseModel;
use core\abstract\BasePivot;

/**
 * 管理员-租户列表
 *
 * @author Mr.April
 * @since  1.0
 */
class SysAdminTenant extends BasePivot
{

    /**
     * 主键类型（雪花 ID 是字符串）
     */
    protected $keyType = 'string';

    /**
     * 主键是否自增（雪花 ID 不自增）
     */
    public $incrementing = false;

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $table = 'sys_admin_tenant';

    protected $appends = ['created_date', 'updated_date'];

    protected $fillable = [
        "id",
        "admin_id",
        "tenant_id",
        "is_super",
        "is_primary",
        "is_default",
        "priority",
        "created_at",
        "updated_at",
    ];

    /**
     * 管理消息-列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function message(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SysMessage::class, 'receiver_id', 'admin_id');
    }

    /**
     * 关联租户
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tenant(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Tenant::class, 'id', 'tenant_id');
    }

    /**
     * 关联租户-列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tenants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tenant::class, 'id', 'tenant_id');
    }

    /**
     * 默认链接
     */
    protected function initialize()
    {
        $this->connection = config('database.default');
    }
}
