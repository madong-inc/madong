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

namespace app\model\la\system;

use madong\basic\BaseLaORMModel;

/**
 * 菜单模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemMenu extends BaseLaORMModel
{

    protected $table = 'system_menu';

    protected $primaryKey = 'id';

    /**
     * 菜单meta属性
     *
     * @param $data
     *
     * @return array
     */
    public static function getMetaAttribute($data): array
    {
        // 1.构建mate数组
        $newData = [
            'icon'                     => $data['icon'] ?? '',
            'title'                    => $data['title'] ?? '',
            'menuVisibleWithForbidden' => true,
        ];

        // 2.添加fixed锁定菜单标记
        if (isset($data['is_affix']) && ($data['is_affix'] === 1 || $data['is_affix'] === '1')) {
            $newData['order']    = -1;
            $newData['affixTab'] = true;
        }

        // 3.是否隐藏菜单
        if (isset($data['is_show']) && $data['is_show'] == 0) {
            $newData['hideInMenu'] = true;
        }

        // 4.是否缓存
        if (isset($data['is_cache']) && $data['is_cache'] == 1) {
            $newData['keepAlive'] = true;
        }

        //5.是否外链在新窗口打开
        if (isset($data['open_type']) && $data['open_type'] == 1) {
            $newData['link'] = true;
        }
        // 更多参数可以在这边添加
        return $newData;
    }

    /**
     * Id搜索
     */
    public function scopeId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * Type搜索
     */
    public function scopeType($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('type', $value);
        } else {
            $query->where('type', $value);
        }
    }

    public function scopeEnabled($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('enabled', $value);
        } else {
            $query->where('enabled', $value);
        }
    }
}
