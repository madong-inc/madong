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

namespace app\dao\system;

use madong\basic\BaseDao;

class SystemRecycleBinDao extends BaseDao
{

    protected function setModel(): string
    {
               return config('app.model_type') === 'laravelORM'
            ? 'app\\model\\la\\system\\SystemRecycleBin'
            : 'app\\model\\tp\\system\\SystemRecycleBin';
    }

    public function selectList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false)
    {
        return parent::selectList($where, $field, $page, $limit, $order, ['operate'], $search);
    }

    public function get(array|string|int $id)
    {
        return parent::get($id, [], ['operate']);
    }

}
