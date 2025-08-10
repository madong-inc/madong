<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.tech
 */

namespace core\abstract;


use app\common\model\platform\Tenant;
use app\common\scopes\global\AccessScope;
use app\common\scopes\global\TenantScope;
use Carbon\Carbon;
use core\uuid\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use core\context\TenantContext;


class BasePivot extends Pivot
{


    /**
     * 指明模型的ID是否自动递增。
     *
     * @var bool
     */
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const DELETED_AT = 'deleted_at';

    protected $appends = [];

    /**
     * 隐藏属性
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 模型日期字段的存储格式。
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * 指示模型是否主动维护时间戳。
     *
     * @var bool
     */
    public $timestamps = false;


    public function __construct(array $data = [])
    {
        $this->initialize();
        parent::__construct($data);
    }

    protected function initialize()
    {
        /**
         * 针对租户库模式切换数据源
         */
        if (TenantContext::isLibraryIsolation()) {
            $this->connection = TenantContext::getDatabaseConnection();
        }
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
        static::addGlobalScope(new AccessScope);
    }

    protected static function boot()
    {
        parent::boot();
        //注册创建事件
        static::creating(function ($model) {
            if (!isset($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Snowflake::generate(); // 生成雪花 ID
            }
            self::setCreatedBy($model);
        });

        // 注册更新事件
        static::updating(function ($model) {
            self::setUpdatedBy($model);
        });

        // 注册删除事件
        static::deleted(function ($model) {

        });
    }

    /**
     * 获取主键名称
     *
     * @return string
     */
    public function getPk(): string
    {
        return $this->getKeyName();
    }

    /**
     * 获取模型的字段列表
     *
     * @return array
     */
    public function getFields(): array
    {
        try {
            $tableName     = $this->getTable();
            $connection    = $this->getConnection();
            $prefix        = $connection->getTablePrefix();
            $fullTableName = $prefix . $tableName;
            $fields        = $connection->select("SHOW COLUMNS FROM `{$fullTableName}`");
            return array_map(function ($column) {
                return $column->Field;
            }, $fields);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 追加创建时间
     *
     * @return string|null
     */
    public function getCreatedDateAttribute(): ?string
    {
        if ($this->getAttribute($this->getCreatedAtColumn())) {
            try {
                $timestamp = $this->getRawOriginal($this->getCreatedAtColumn());
                if (empty($timestamp)) {
                    return null;
                }
                $carbonInstance = Carbon::createFromTimestamp($timestamp);
                return $carbonInstance->setTimezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * 追加更新时间
     *
     * @return string|null
     */
    public function getUpdatedDateAttribute(): ?string
    {
        if ($this->getAttribute($this->getUpdatedAtColumn())) {
            try {
                $timestamp = $this->getRawOriginal($this->getUpdatedAtColumn());
                if (empty($timestamp)) {
                    return null;
                }
                $carbonInstance = Carbon::createFromTimestamp($timestamp);
                return $carbonInstance->setTimezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * 设置创建人
     *
     * @param Model $model
     *
     * @return void
     */
    private static function setCreatedBy(Model $model): void
    {
        $uid = getCurrentUser();
        if ($uid && $model->isFillable('created_by')) {
            $model->setAttribute('created_by', $uid);
        }
    }

    /**
     * 设置更新人
     *
     * @param Model $model
     *
     * @return void
     */
    private static function setUpdatedBy(Model $model): void
    {
        $uid = getCurrentUser();
        if ($uid && $model->isFillable('updated_by')) {
            $model->setAttribute('updated_by', $uid);
        }
    }

    /**
     * 自动设置租户ID
     *
     * @param \support\Model $model
     */
    private static function setTenantId(Model $model): void
    {
        // 如果模型本身是 Tenant 类型，则无需设置 tenant_id
        if ($model instanceof Tenant) {
            return;
        }

        // 如果当前没有登录用户，则不设置 tenant_id
        if (!getCurrentUser(true)) {
            return;
        }

        // 如果模型不允许填充 tenant_id 字段，则跳过
        if (!$model->isFillable('tenant_id')) {
            return;
        }

        // 如果 tenant_id 已经被手动设置过，则不覆盖（避免外部赋值被覆盖）
        if (!empty($model->getAttribute('tenant_id'))) {
            return;
        }

        // 如果不是字段隔离模式，则不需要设置 tenant_id
        if (!TenantContext::isFieldIsolation()) {
            return;
        }

        // 从上下文中获取租户 ID，并设置到模型中（仅当 tenant_id 不为 null 时）
        $tenantId = TenantContext::getTenantId();
        if ($tenantId !== null) {
            $model->setAttribute('tenant_id', $tenantId);
        }
    }

}
