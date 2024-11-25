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
 * 字典模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemDict extends BaseTpORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_dict';

    public function setIdAttr($value)
    {
        $this->set($this->pk, (string)$value);
    }

    /**
     * ID-搜索器
     *
     * @param $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (!empty($value)) {
            $queryMethod = is_array($value) ? 'whereIn' : 'where';
            $query->$queryMethod('id', $value);
        }
    }

    /**
     * 字典名称-搜索器
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
     * 字典代码-搜索器
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

    /**
     * 字典数据
     *
     * @return \think\model\relation\HasMany
     */
    public function items(): \think\model\relation\HasMany
    {
        return $this->hasMany(SystemDictItem::class, 'dict_id', 'id');
    }

}
