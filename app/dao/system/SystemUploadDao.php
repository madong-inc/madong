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

class SystemUploadDao extends BaseDao
{

    protected function setModel(): string
    {
                return config('app.model_type') === 'laravelORM'
            ? 'app\\model\\la\\system\\SystemUpload'
            : 'app\\model\\tp\\system\\SystemUpload';
    }

    public function selectList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false)
    {
        if (Config('app.model_type', 'thinkORM') == 'thinkORM') {
            return parent::selectList($where, $field, $page, $limit, $order, ['created', 'updated'], $search)->toArray();
        } else {
            return parent::selectList($where, $field, $page, $limit, $order, ['createds', 'updateds'], $search);
        }
    }
}
