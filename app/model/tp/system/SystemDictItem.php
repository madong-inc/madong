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

namespace app\model\tp\system;

use madong\basic\BaseTpORMModel;

/**
 * 字典数据
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemDictItem extends BaseTpORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_dict_item';

    /**
     * 关键字搜索
     */
    public function searchKeywordsAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('label|code', 'LIKE', "%$value%");
        }
    }

    /**
     * 状态-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('status', $value);
        }
    }

    /**
     * 字典ID-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchDictIdAttr($query, $value)
    {
        if (!empty($value)) {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('dict_id', $value);
        }
    }

    /**
     * 字典标识-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchCodeAttr($query, $value)
    {
        if (!empty($value)) {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('code', $value);
        }
    }

    /**
     * Value- 搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchValueAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('value', 'LIKE', "%$value%");
        }
    }

    /**
     * Label-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchLabelAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('label', 'LIKE', "%$value%");
        }
    }

    /**
     * 获取器-扩展属性
     *
     * @param $value
     *
     * @return mixed|object
     */
    public function getExtAttr($value): mixed
    {
        if (is_null($value) || $value === '') {
            return (object)[];
        }
        return json_decode($value, 1);
    }

}
