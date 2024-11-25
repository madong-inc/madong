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

namespace app\services\system;

use app\dao\system\SystemDictDao;
use madong\basic\BaseService;
use ReflectionClass;
use ReflectionException;
use support\Container;

/**
 * 数据字段服务
 *
 * @author Mr.April
 * @since  1.0
 * @method update($where, $data)
 */
class SystemDictService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemDictDao::class);
    }

    /**
     * 字典项获取
     *
     * @param string $code
     *
     * @return array
     */
//    public function findItemsByCode(string $code): array
//    {
//        $map1   = [
//            'code'    => $code,
//            'enabled' => 1,
//        ];
//        $result = $this->get($map1, ['*'], ['items']);
//        if (empty($result)) {
//            return [];
//        }
//        $items = Config('app.model_type', 'thinkORM') == 'laravelORM' ? $result->items : $result->getData('items');
//        if (empty($items)) {
//            return [];
//        }
//        $dataType = Config('app.model_type', 'thinkORM') == 'laravelORM' ? $result->data_type : $result->getData('data_type');
//        foreach ($items as $item) {
//            $value = Config('app.model_type', 'thinkORM') == 'laravelORM' ? $item->value : $item->getData('value');
//            if ($dataType == 1) {
//                $item->value = (string)$value; // 转换为字符串
//            } elseif ($dataType == 2) {
//                $item->value = (int)$value; // 转换为整型
//            }
//        }
//        if (Config('app.model_type', 'thinkORM') == 'laravelORM') {
//            return $items->makeHidden(['id'])->map(function ($item) {
//                return [
//                    'label' => $item->label,
//                    'value' => $item->value,
//                    'ext'   => $item->ext,
//                ];
//            })->toArray();
//        } else {
//            return array_map(function ($item) {
//                return [
//                    'label' => $item->getData('label'),
//                    'value' => $item->getData('value'),
//                    'ext'   => $item->getData('ext'),
//                ];
//            }, $items);
//        }
//    }

    /**
     * 扫描枚举目录
     *
     * @param array $directories
     *
     * @return array
     */
    public function scanEnums(array $directories): array
    {
        $enumList = [];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*.php');

            foreach ($files as $file) {
                $className = 'app\\num\\' . pathinfo($file, PATHINFO_FILENAME);
                if ($this->isEnumClass($className)) {
                    $enumInfo = $this->getEnumInfo($className);
                    if ($enumInfo) {
                        $enumList[] = $enumInfo;
                    }
                }
            }
        }
        return $enumList;
    }

    /**
     * 是否枚举类
     *
     * @param $className
     *
     * @return bool
     */
    private function isEnumClass($className): bool
    {
        try {
            $reflectionClass = new ReflectionClass($className);
            return $reflectionClass->isEnum();
        } catch (ReflectionException $e) {
            var_dump($e->getMessage());
            return false; // 如果遇到错误则返回 false
        }
    }

    /**
     * 枚举数据
     *
     * @param $className
     *
     * @return array
     */
    private function getEnumInfo($className): array
    {
        $reflectionClass = new ReflectionClass($className);
        $enumInfo        = [
            'name'     => method_exists($className, 'getName') ? $className::getName() : $reflectionClass->getName(), // 枚举类的名称
            'dict_key' => $className,
            'items'    => [],
        ];
        foreach ($reflectionClass->getConstants() as $constantName => $constantValue) {
            $enumInstance        = $reflectionClass->getConstant($constantName);
            $enumInfo['items'][] = [
                'dict_item_key'   => $constantName, // 枚举成员的字典键名
                'dict_item_value' => method_exists($className, 'getDescription') ? $enumInstance->getDescription() : $constantValue, // 示例值
            ];
        }
        return $enumInfo; // 返回构建的枚举类信息
    }
}
