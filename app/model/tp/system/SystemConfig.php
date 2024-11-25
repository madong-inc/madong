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
 * 配置模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemConfig extends BaseTpORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_config';

    /**
     * 分组代码-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchGroupCodeAttr($query, $value)
    {
        if (!empty($value)) {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('group_code', $value);
        }
    }

    /**
     * 配置名称-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if (!empty($value)) {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('name', $value);
        }
    }

    /**
     * 唯一编码-搜索器
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
     * 状态-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchEnableAttr($query, $value)
    {
        if ($value !== '') {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('enable', $value);
        }
    }
}
