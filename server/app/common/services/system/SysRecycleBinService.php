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

namespace app\common\services\system;

use app\common\dao\system\SysRecycleBinDao;
use app\common\model\system\SysRecycleBin;
use app\common\scopes\global\TenantScope;
use core\exception\handler\AdminException;
use core\abstract\BaseService;
use core\context\TenantContext;
use support\Container;
use support\Db;

/**
 * 回收站服务
 *
 * @author Mr.April
 * @since  1.0
 */
class SysRecycleBinService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysRecycleBinDao::class);
    }

    /**
     * 数据恢复
     *
     * @param int $recycleId
     *
     * @throws \Throwable
     */
    public function restoreRecycleBin(int $recycleId)
    {
        $this->transaction(function () use ($recycleId) {
            $record = $this->dao->get($recycleId, null, [], '', [TenantScope::class]);
            if (empty($record)) {
                throw new AdminException("回收站记录不存在");
            }
            // 3. 动态恢复数据
            $this->restoreOriginalData($record);

            // 4. 删除回收站记录
            $record->delete();
        });
    }

    /**
     * 恢复原始数据
     */
    protected function restoreOriginalData(SysRecycleBin $record)
    {
        $tableName    = $record->table_name;
        $config       = self::getTableConfig($tableName);
        $storage_mode = $config['storage_mode'];
        $connection   = config('database.default');
        if ($storage_mode == 'isolated') {
            $connection = TenantContext::getDatabaseConnection();
        }
        $tableData              = json_decode($record->getData('data'), true);
        $tableData['tenant_id'] = TenantContext::getTenantId();
        $columns                = $this->getTableColumns($tableName, $connection);
        $tableData              = array_intersect_key($tableData, array_flip($columns));
        Db::connection($connection)->table($tableName)->insert($tableData);
    }

    /**
     * 获取还原表的数据列
     *
     * @param string $tableName
     * @param string $connection
     *
     * @return array
     */
    private function getTableColumns(string $tableName, string $connection = ''): array
    {
        return Db::connection($connection)->getSchemaBuilder()->getColumnListing($tableName);
    }

    /**
     * 获取表配置（合并全局+表级配置）
     *
     * @param $table
     *
     * @return array
     */
    // 获取合并配置
    public static function getTableConfig(string $table): array
    {
        return array_merge(
            config('recycle_bin.default'),
            config("recycle_bin.tables.{$table}", []),
            ['tenant' => config('recycle_bin.tenant')]
        );
    }

}
