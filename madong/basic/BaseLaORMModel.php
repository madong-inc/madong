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

namespace madong\basic;

use app\services\system\SystemRecycleBinService;
use Illuminate\Support\Carbon;
use madong\exception\AdminException;
use madong\utils\Snowflake;
use support\Container;
use support\Db;
use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseLaORMModel extends Model
{
    private const WORKER_ID = 1;
    private const DATA_CENTER_ID = 1;

    /**
     * 指明模型的ID是否自动递增。
     *
     * @var bool
     */
    public $incrementing = false;

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

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
     * 存储动态隐藏字段
     *
     * @var array
     */
    protected array $dynamicHidden = [];

    protected $guarded = [];

    /**
     * 雪花算法实例化类
     *
     * @var Snowflake|null
     */
    private static ?Snowflake $snowflake = null;

    /**
     * 兼容tp写法
     *
     * @return string
     */
    public function getPk(): string
    {
        return $this->getKeyName();
    }

    protected static function boot()
    {
        parent::boot();
        //注册创建事件
        static::creating(function ($model) {
            if (!isset($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = self::generateSnowflakeID(); // 生成雪花 ID
            }
            self::setCreatedBy($model);
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
     * 兼容tp写法
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
     * 兼容 TP
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * 获取当前模型的字段列表
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
     * 兼容tp 重写动态输出隐藏
     *
     * @param array $fields
     *
     * @return $this
     */
    public function hidden(array $fields): static
    {
        $this->dynamicHidden = array_merge($this->dynamicHidden, $fields);
        return $this; // 支持链式调用
    }

    public function getCreateTimeAttribute($value): string
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getExpiresTimeAttribute($value): string
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdateTimeAttribute($value): string
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public static function onAfterDelete(Model $model)
    {
        try {
            if ($model->isSoftDeleteEnabled()) {
                return;
            }
            $table     = $model->getTable();
            $tableData = $model->getAttributes();
            $prefix    = $model->getConnection()->getTablePrefix();
            if (self::shouldStoreInRecycleBin($table)) {
                $data                    = self::prepareRecycleBinData($tableData, $table, $prefix);
                $systemRecycleBinService = Container::make(SystemRecycleBinService::class);
                $systemRecycleBinService->save($data);
            }
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
        if ($uid && $model->isFillable('create_by')) {
            $model->setAttribute('create_by', $uid);
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
        if ($uid && $model->isFillable('update_by')) {
            $model->setAttribute('update_by', $uid);
        }
    }

    /**
     *  实力话雪花算法
     *
     * @return Snowflake
     */
    private static function createSnowflake(): Snowflake
    {
        if (self::$snowflake == null) {
            self::$snowflake = new Snowflake(self::WORKER_ID, self::DATA_CENTER_ID);
        }
        return self::$snowflake;
    }

    /**
     * 生成雪花ID
     *
     * @return int
     */
    private static function generateSnowflakeID(): int
    {
        $snowflake = self::createSnowflake();
        return $snowflake->nextId();
    }

    private static function shouldStoreInRecycleBin($table): bool
    {
        return config('app.store_in_recycle_bin') && !in_array($table, config('app.exclude_from_recycle_bin'));
    }

    private static function prepareRecycleBinData($tableData, $table, $prefix): array
    {
        return [
            'data'         => json_encode($tableData),
            'table_name'   => $table,
            'table_prefix' => $prefix,
            'enabled'      => 0,
            'ip'           => request()->getRealIp(),
            'operate_id'   => getCurrentUser(),
        ];
    }

}
