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

class SystemDictDao extends BaseDao
{

    protected function setModel(): string
    {
               return config('app.model_type') === 'laravelORM'
            ? 'app\\model\\la\\system\\SystemDict'
            : 'app\\model\\tp\\system\\SystemDict';
    }

    /**
     * 通过code获取字典项
     *
     * @param string $code
     *
     * @return array
     */
    public function findItemsByCode(string $code): array
    {
        $map1   = [
            'code'    => $code,
            'enabled' => 1,
        ];
        $result = $this->get($map1, ['*'], ['items']);
        if (empty($result)) {
            return [];
        }
        $items = Config('app.model_type', 'thinkORM') == 'laravelORM' ? $result->items : $result->getData('items');
        if (empty($items)) {
            return [];
        }
        $dataType = Config('app.model_type', 'thinkORM') == 'laravelORM' ? $result->data_type : $result->getData('data_type');
        foreach ($items as $item) {
            $value = Config('app.model_type', 'thinkORM') == 'laravelORM' ? $item->value : $item->getData('value');
            if ($dataType == 1) {
                $item->value = (string)$value; // 转换为字符串
            } elseif ($dataType == 2) {
                $item->value = (int)$value; // 转换为整型
            }
        }
        if (Config('app.model_type', 'thinkORM') == 'laravelORM') {
            return $items->makeHidden(['id'])->map(function ($item) {
                return [
                    'label' => $item->label,
                    'value' => $item->value,
                    'ext'   => $item->ext,
                ];
            })->toArray();
        } else {
            return $items->visible(['label', 'value', 'ext'])->toArray();
        }
    }
}
