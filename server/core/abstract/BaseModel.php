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
use app\common\model\system\SysRecycleBin;
use app\common\scopes\global\AccessScope;
use app\common\scopes\global\TenantScope;
use app\common\scopes\global\TenantSharedTableScope;
use app\common\services\system\SysRecycleBinService;
use Carbon\Carbon;
use core\exception\handler\AdminException;
use core\uuid\Snowflake;
use Illuminate\Database\Eloquent\SoftDeletes;
use core\context\TenantContext;
use support\Container;
use support\Model;

class BaseModel extends Model
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
    protected $hidden = ['tenant_id'];

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
    public $timestamps = true;

    /**
     * 雪花算法实例
     *
     * @var Snowflake|null
     */
    protected static ?Snowflake $snowflake = null;

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
        // 1. 默认添加 TenantScope 和 AccessScope
        static::addGlobalScope(new TenantScope);
        static::addGlobalScope(new AccessScope);

        // 2. 检查当前模型是否在共享表配置中
        $sharedModel  = config('tenant.shared_model', []);
        $currentModel = static::class; // 获取当前模型的表名
        if (in_array($currentModel, $sharedModel)) {
            static::addGlobalScope(new TenantSharedTableScope());
        }
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
            self::setTenantId($model);
        });

        // 注册更新事件
        static::updating(function ($model) {
            self::setUpdatedBy($model);
        });

        // 注册删除事件
        static::deleted(function ($model) {
            self::onAfterDelete($model);
        });
    }

    /**
     * 是否开启软删
     *
     * @return bool
     */
    public static function isSoftDeleteEnabled(): bool
    {
        return in_array(SoftDeletes::class, class_uses(static::class));
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
     * 获取模型字段数据
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getData(string $field): mixed
    {
        return $this->attributes[$field] ?? null;
    }

    /**
     * 写入模型字段数据
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
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
     * 删除事件
     *
     * @param \support\Model $model
     *
     * @throws \core\exception\handler\AdminException
     */
    public static function onAfterDelete(Model $model)
    {
        try {
            $table = $model->getTable();
            /** @var SysRecycleBinService $recycleService */
            $service = Container::make(SysRecycleBinService::class);
            $config  = $service->getTableConfig($table);

            // 检查是否启用回收站
            if (!$config['enabled'] || $model->isSoftDeleteEnabled() || $config['strategy'] === 'logical') {
                return;
            }
            $prefix = $model->getConnection()->getTablePrefix();

            // 准备数据（排除敏感字段）
            $tableData                = array_except($model->getAttributes(), $config['exclude_fields'] ?? []);
            $tableData['original_id'] = $model->getAttribute($model->getPk());

            $data = self::prepareRecycleBinData($tableData, $table, $prefix);
            // 动态选择存储方式
            $handler = $config['storage_mode'] === 'central'
                ? fn() => SysRecycleBin::centralConnection()
                : fn() => SysRecycleBin::tenantConnection();

            $handler()->create($data);
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
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

        // 如果不是字段隔离模式以及共享表，则不需要设置 tenant_id
//        if (!TenantContext::isFieldIsolation()) {
//            $sharedModel  = config('tenant.shared_model', []);
//            $currentModel = static::class; // 获取当前模型的表名
//            if (!in_array($currentModel, $sharedModel)) {
//                return;
//            }
//        }

        // 从上下文中获取租户 ID，并设置到模型中（仅当 tenant_id 不为 null 时）
        $tenantId = TenantContext::getTenantId();
        if ($tenantId !== null) {
            $model->setAttribute('tenant_id', $tenantId);
        }
    }

    private static function prepareRecycleBinData($tableData, $table, $prefix): array
    {
        return [
            'data'         => $tableData,
            'original_id'  => $tableData['id'] ?? '',
            'table_name'   => $table,
            'table_prefix' => $prefix,
            'enabled'      => 0,
            'tenant_id'    => TenantContext::getTenantId(),
            'ip'           => request()->getRealIp(),
            'operate_id'   => getCurrentUser(),
        ];
    }

}
