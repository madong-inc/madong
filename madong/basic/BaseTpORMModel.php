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
use madong\exception\AdminException;
use madong\exception\ApiException;
use madong\trait\ModelTrait;
use madong\utils\Snowflake;
use support\Container;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @author Mr.April
 * @since  1.0
 */
class BaseTpORMModel extends Model
{
    use ModelTrait;

    // 删除时间
    protected $deleteTime = 'delete_time';
    // 添加时间
    protected $createTime = 'create_time';
    // 更新时间
    protected $updateTime = 'update_time';
    // 隐藏字段
    protected $hidden = ['delete_time'];
    // 只读字段
    protected $readonly = ['created_by', 'create_time'];

    private const WORKER_ID = 1;
    private const DATA_CENTER_ID = 1;

    /**
     * 雪花算法实例化类
     *
     * @var Snowflake|null
     */
    private static ?Snowflake $snowflake = null;

    /**
     * 获取模型定义的字段列表
     *
     * @return mixed
     */
    public function getFields(): mixed
    {
        return $this->getTableFields();
    }

    /**
     * 是否开启软删
     *
     * @return bool
     */
    public function isSoftDeleteEnabled(): bool
    {
        return in_array(SoftDelete::class, class_uses(static::class));
    }

    /**
     * 获取模型定义的数据库表名【全称】
     *
     * @return string
     */
    public static function getTableName(): string
    {
        $self = new static();
        return $self->getConfig('prefix') . $self->name;
    }

    /**
     * 新增事件
     *
     * @param Model $model
     *
     * @return void
     * @throws ApiException
     */
    public static function onBeforeInsert(Model $model): void
    {
        try {
            self::setCreatedBy($model);
            self::setPrimaryKey($model);
        } catch (\Exception $e) {
            throw new MadongException($e->getMessage());
        }
    }

    /**
     * 写入事件
     *
     * @param Model $model
     *
     * @return void
     */
    public static function onBeforeWrite(Model $model): void
    {
        self::setUpdatedBy($model);
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
        if ($uid) {
            $model->setAttr('created_by', $uid);
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
        if ($uid) {
            $model->setAttr('updated_by', $uid);
        }
    }

    /**
     * 设置主键
     *
     * @param Model $model
     *
     * @return void
     */
    private static function setPrimaryKey(Model $model): void
    {
        $flakeId             = $model->{$model->pk} ?? self::generateSnowflakeID();
        $model->{$model->pk} = $flakeId;
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

    /**
     * 删除-事件
     *
     * @param \think\Model $model
     */
    public static function onAfterDelete(Model $model)
    {
        if ($model->isSoftDeleteEnabled()) {
            return;
        }
        $table     = $model->getName();
        $tableData = $model->getData();
        $prefix    = $model->getConfig('prefix');
        try {
            if (self::shouldStoreInRecycleBin($table)) {
                $data                    = self::prepareRecycleBinData($tableData, $table, $prefix);
                $systemRecycleBinService = Container::make(SystemRecycleBinService::class);
                $systemRecycleBinService->save($data);
            }
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
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
